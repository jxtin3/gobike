/*
 * Dashboard charts: bar (check-ups), line (high / low readings) and donut (abnormal readings by vital sign).
 * Hand-built SVG: smooth entrance animations, hover and keyboard tooltips, clickable legend and bars.
 */
(() => {
    const root = document.querySelector('[data-analytics-dashboard]');
    if (!root) return;

    const period = root.dataset.period || 'this period';
    const unit = root.dataset.unit === 'day' ? 'day' : 'month';
    const vitals = JSON.parse(root.dataset.vitals || '{}');
    const checkups = JSON.parse(root.dataset.checkups || '[]');
    const pieData = JSON.parse(root.dataset.pie || '[]');
    const cards = [...root.querySelectorAll('[data-kpi]')];
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const entranceAnimation = root.dataset.entrance === 'true' && !reduceMotion;
    const trendTitle = root.querySelector('[data-trend-title]');
    const barChart = root.querySelector('[data-bar-chart]');
    const lineChart = root.querySelector('[data-line-chart]');
    const pieChart = root.querySelector('[data-pie-chart]');
    const pieLegend = root.querySelector('[data-pie-legend]');
    const pieTotal = root.querySelector('[data-pie-total]');
    const seriesButtons = [...root.querySelectorAll('[data-series]')];

    const COLORS = { high: '#e5484d', low: '#3b82f6', bar: '#3b6fd4', barDark: '#244b91' };
    const PIE_COLORS = ['#e5484d', '#f59e0b', '#10b981', '#6366f1'];
    const SERIES_LABEL = { high: 'High', low: 'Low' };
    const EASE = 'cubic-bezier(.2,.7,.3,1)';
    const numberFormat = new Intl.NumberFormat();
    const SVG_NS = 'http://www.w3.org/2000/svg';

    let entrancePending = entranceAnimation;
    let selectedKey = null;
    const visible = { high: true, low: true };
    let gradientId = 0;

    /* ---------- helpers ---------- */

    const node = (parent, name, attrs = {}, text = null) => {
        const el = document.createElementNS(SVG_NS, name);
        Object.entries(attrs).forEach(([key, value]) => el.setAttribute(key, String(value)));
        if (text !== null) el.textContent = text;
        parent.append(el);
        return el;
    };

    const makeSvg = (width, height, label, extraClass = '') => {
        const svg = document.createElementNS(SVG_NS, 'svg');
        svg.setAttribute('viewBox', `0 0 ${width} ${height}`);
        svg.setAttribute('class', `analytics-svg ${extraClass}`.trim());
        svg.setAttribute('role', 'img');
        svg.setAttribute('aria-label', label);
        svg.setAttribute('focusable', 'false');
        svg.dataset.width = width;
        return svg;
    };

    const motion = (el, keyframes, options, animate) => {
        if (!animate || reduceMotion || typeof el.animate !== 'function') return;
        el.animate(keyframes, { duration: 700, easing: EASE, fill: 'backwards', ...options });
    };

    const showEmpty = (target, message) => {
        target.replaceChildren();
        const empty = document.createElement('p');
        empty.className = 'analytics-empty';
        empty.textContent = message;
        target.append(empty);
    };

    /** Round the top of an axis up to a tidy number and pick a step, e.g. 0, 5, 10, 15, 20. */
    function niceScale(max) {
        if (max <= 4) return { top: Math.max(1, Math.ceil(max)), step: 1 };
        const raw = max / 4;
        const power = 10 ** Math.floor(Math.log10(raw));
        const fraction = raw / power;
        const step = (fraction <= 1 ? 1 : fraction <= 2 ? 2 : fraction <= 5 ? 5 : 10) * power;
        return { top: Math.ceil(max / step) * step, step };
    }

    /** Smooth curve through the points that never overshoots (monotone cubic). */
    function smoothPath(points) {
        if (points.length < 2) return '';
        const slopes = points.map((point, index) => {
            if (index === 0) return (points[1].y - point.y) / (points[1].x - point.x);
            if (index === points.length - 1) {
                const previous = points[index - 1];
                return (point.y - previous.y) / (point.x - previous.x);
            }
            const previous = points[index - 1];
            const next = points[index + 1];
            const previousSlope = (point.y - previous.y) / (point.x - previous.x);
            const nextSlope = (next.y - point.y) / (next.x - point.x);
            return previousSlope * nextSlope <= 0 ? 0 : (previousSlope + nextSlope) / 2;
        });

        let path = `M ${points[0].x} ${points[0].y}`;
        for (let index = 0; index < points.length - 1; index += 1) {
            const start = points[index];
            const end = points[index + 1];
            const width = end.x - start.x;
            const delta = (end.y - start.y) / width;

            if (delta === 0) {
                slopes[index] = 0;
                slopes[index + 1] = 0;
            } else {
                const alpha = slopes[index] / delta;
                const beta = slopes[index + 1] / delta;
                const magnitude = alpha * alpha + beta * beta;
                if (magnitude > 9) {
                    const scale = 3 / Math.sqrt(magnitude);
                    slopes[index] = scale * alpha * delta;
                    slopes[index + 1] = scale * beta * delta;
                }
            }

            path += ` C ${start.x + width / 3} ${start.y + slopes[index] * width / 3}`
                + ` ${end.x - width / 3} ${end.y - slopes[index + 1] * width / 3}`
                + ` ${end.x} ${end.y}`;
        }
        return path;
    }

    /** One floating tooltip per chart, positioned in the chart's own box. */
    function makeTooltip(host) {
        const tip = document.createElement('div');
        tip.className = 'chart-tip';
        tip.hidden = true;
        tip.setAttribute('role', 'status');
        host.append(tip);

        return {
            show({ title, rows = [], hint = '' }, x, y) {
                tip.replaceChildren();
                const heading = document.createElement('b');
                heading.textContent = title;
                tip.append(heading);
                rows.forEach(({ color, label, value }) => {
                    const row = document.createElement('span');
                    row.className = 'chart-tip-row';
                    const dot = document.createElement('i');
                    dot.style.background = color;
                    const name = document.createElement('span');
                    name.textContent = label;
                    const number = document.createElement('strong');
                    number.textContent = value;
                    row.append(dot, name, number);
                    tip.append(row);
                });
                if (hint) {
                    const note = document.createElement('small');
                    note.textContent = hint;
                    tip.append(note);
                }
                tip.hidden = false;
                const width = tip.offsetWidth;
                const height = tip.offsetHeight;
                const left = Math.min(Math.max(x - width / 2, 4), Math.max(4, host.clientWidth - width - 4));
                const top = y - height - 12 < 0 ? y + 18 : y - height - 12;
                tip.style.transform = `translate(${left}px, ${top}px)`;
            },
            hide() {
                tip.hidden = true;
            },
        };
    }

    /** Convert a point in SVG units to pixels inside the host element. */
    function toHost(host, svg, x, y) {
        const svgBox = svg.getBoundingClientRect();
        const hostBox = host.getBoundingClientRect();
        const scale = svgBox.width / Number(svg.dataset.width);
        return { x: svgBox.left - hostBox.left + x * scale, y: svgBox.top - hostBox.top + y * scale };
    }

    /** Pointer x in SVG units. */
    function pointerX(svg, event) {
        const box = svg.getBoundingClientRect();
        return (event.clientX - box.left) * (Number(svg.dataset.width) / box.width);
    }

    /** Arrow keys move through the chart's points, Enter opens the active one. */
    function bindKeyboard(svg, count, setActive, clear, open) {
        let index = -1;
        svg.setAttribute('tabindex', '0');
        svg.addEventListener('keydown', (event) => {
            const keys = {
                ArrowRight: () => Math.min(count - 1, index + 1),
                ArrowLeft: () => Math.max(0, index - 1),
                Home: () => 0,
                End: () => count - 1,
            };
            if (event.key === 'Enter' && index >= 0 && open) {
                open(index);
                return;
            }
            if (!keys[event.key]) return;
            event.preventDefault();
            index = index < 0 && event.key !== 'End' ? 0 : keys[event.key]();
            setActive(index);
        });
        svg.addEventListener('blur', () => {
            index = -1;
            clear();
        });
        return (value) => { index = value; };
    }

    function axis(svg, ticks, geometry, format = (value) => numberFormat.format(value)) {
        const { left, right, width, y } = geometry;
        ticks.forEach((value) => {
            node(svg, 'line', { x1: left, x2: width - right, y1: y(value), y2: y(value), class: value === 0 ? 'analytics-baseline' : 'analytics-gridline' });
            node(svg, 'text', { x: left - 8, y: y(value) + 4, 'text-anchor': 'end', class: 'analytics-axis-label' }, format(value));
        });
    }

    const tickList = (scale) => Array.from({ length: Math.round(scale.top / scale.step) + 1 }, (_, i) => i * scale.step);

    /** Thin out x labels when there are too many to read, e.g. the days of a month. */
    const labelEvery = (slot) => Math.max(1, Math.ceil(26 / slot));

    /* ---------- bar chart ---------- */

    function renderBars(items, animate = true) {
        if (!items.some((item) => item.count)) {
            showEmpty(barChart, `No patient check-ups recorded in ${period}.`);
            return;
        }

        barChart.replaceChildren();
        const width = Math.max(300, Math.round(barChart.clientWidth));
        const height = 270;
        const geometry = { left: 40, right: 10, top: 26, bottom: 36, width };
        const chartWidth = width - geometry.left - geometry.right;
        const chartHeight = height - geometry.top - geometry.bottom;
        const scale = niceScale(Math.max(...items.map((item) => item.count)));
        geometry.y = (value) => geometry.top + chartHeight - chartHeight * value / scale.top;

        const svg = makeSvg(width, height, `Bar chart of patient check-ups for each ${unit} of ${period}. Use the arrow keys to move between bars.`, 'analytics-svg-bars');
        const id = `bar-gradient-${gradientId += 1}`;
        const defs = node(svg, 'defs');
        const gradient = node(defs, 'linearGradient', { id, x1: 0, y1: 0, x2: 0, y2: 1 });
        node(gradient, 'stop', { offset: '0%', 'stop-color': COLORS.bar });
        node(gradient, 'stop', { offset: '100%', 'stop-color': COLORS.barDark, 'stop-opacity': 0.85 });

        axis(svg, tickList(scale), geometry);

        const slot = chartWidth / items.length;
        const barWidth = Math.min(36, slot * 0.62);
        const peak = items.reduce((best, item, index) => (item.count > items[best].count ? index : best), 0);
        const showValues = items.length <= 12;
        const every = labelEvery(slot);
        const bars = [];
        const barGroup = node(svg, 'g', { class: 'analytics-bars' });

        items.forEach((item, index) => {
            const x = geometry.left + slot * index + (slot - barWidth) / 2;
            const barHeight = item.count ? Math.max(4, chartHeight * item.count / scale.top) : 0;
            const bar = node(barGroup, 'rect', {
                x,
                y: geometry.top + chartHeight - barHeight,
                width: barWidth,
                height: barHeight,
                rx: Math.min(8, barWidth / 2.5),
                fill: `url(#${id})`,
                class: `analytics-bar${index === peak ? ' is-peak' : ''}`,
            });
            motion(bar, [{ transform: 'scaleY(0)' }, { transform: 'scaleY(1)' }], { duration: 650, delay: 120 + index * (items.length > 12 ? 14 : 45) }, animate);
            bars.push(bar);

            if (showValues && item.count) {
                const value = node(barGroup, 'text', { x: x + barWidth / 2, y: geometry.top + chartHeight - barHeight - 7, 'text-anchor': 'middle', class: 'analytics-bar-value' }, numberFormat.format(item.count));
                motion(value, [{ opacity: 0 }, { opacity: 1 }], { duration: 400, delay: 500 + index * 45 }, animate);
            }

            if (index % every === 0) {
                node(svg, 'text', { x: x + barWidth / 2, y: height - 12, 'text-anchor': 'middle', class: 'analytics-axis-label' }, item.shortLabel);
            }
        });

        barChart.append(svg);
        const tooltip = makeTooltip(barChart);
        let active = -1;

        const setActive = (index) => {
            if (index === active) return;
            active = index;
            bars.forEach((bar, i) => bar.classList.toggle('is-active', i === index));
            svg.classList.toggle('is-hovering', index >= 0);
            if (index < 0) {
                tooltip.hide();
                svg.style.cursor = '';
                return;
            }
            const item = items[index];
            const barTop = geometry.top + chartHeight - (item.count ? Math.max(4, chartHeight * item.count / scale.top) : 0);
            const at = toHost(barChart, svg, geometry.left + slot * index + slot / 2, barTop);
            tooltip.show({
                title: item.label,
                rows: [{ color: COLORS.bar, label: item.count === 1 ? 'Check-up' : 'Check-ups', value: numberFormat.format(item.count) }],
                hint: item.url ? `Click to open ${item.label}` : '',
            }, at.x, at.y);
            svg.style.cursor = item.url ? 'pointer' : '';
        };
        const clear = () => setActive(-1);
        const open = (index) => { if (items[index]?.url) window.location.href = items[index].url; };
        const syncKeyboardIndex = bindKeyboard(svg, items.length, setActive, clear, open);

        const indexAt = (event) => {
            const index = Math.floor((pointerX(svg, event) - geometry.left) / slot);
            return index >= 0 && index < items.length ? index : -1;
        };
        svg.addEventListener('pointermove', (event) => { const index = indexAt(event); syncKeyboardIndex(index); setActive(index); });
        svg.addEventListener('pointerleave', clear);
        svg.addEventListener('click', (event) => open(indexAt(event)));
    }

    /* ---------- line chart ---------- */

    function renderLine(vital, animate = true) {
        const months = vital.months;
        if (!months.some((month) => month.high || month.low)) {
            showEmpty(lineChart, `No high or low readings recorded in ${period}.`);
            return;
        }

        lineChart.replaceChildren();
        const width = Math.max(300, Math.round(lineChart.clientWidth));
        const height = 270;
        const geometry = { left: 40, right: 16, top: 20, bottom: 36, width };
        const chartWidth = width - geometry.left - geometry.right;
        const chartHeight = height - geometry.top - geometry.bottom;
        const scale = niceScale(Math.max(...months.flatMap((month) => [month.high, month.low])));
        geometry.y = (value) => geometry.top + chartHeight - chartHeight * value / scale.top;
        const x = (index) => geometry.left + (months.length === 1 ? chartWidth / 2 : chartWidth * index / (months.length - 1));
        const levels = ['low', 'high'].filter((level) => visible[level]);

        const svg = makeSvg(width, height, `Line chart of high and low ${vital.label.toLowerCase()} readings for each ${unit} of ${period}. Use the arrow keys to move between ${unit}s.`, 'analytics-svg-line');
        const defs = node(svg, 'defs');
        const ids = {};
        ['high', 'low'].forEach((level) => {
            ids[level] = `line-gradient-${gradientId += 1}`;
            const gradient = node(defs, 'linearGradient', { id: ids[level], x1: 0, y1: 0, x2: 0, y2: 1 });
            node(gradient, 'stop', { offset: '0%', 'stop-color': COLORS[level], 'stop-opacity': 0.28 });
            node(gradient, 'stop', { offset: '100%', 'stop-color': COLORS[level], 'stop-opacity': 0 });
        });

        axis(svg, tickList(scale), geometry);

        const every = labelEvery(chartWidth / Math.max(1, months.length - 1));
        months.forEach((month, index) => {
            if (index % every === 0) {
                node(svg, 'text', { x: x(index), y: height - 12, 'text-anchor': 'middle', class: 'analytics-axis-label' }, month.shortLabel);
            }
        });

        const baseline = geometry.top + chartHeight;
        levels.forEach((level) => {
            const points = months.map((month, index) => ({ x: x(index), y: geometry.y(month[level]) }));
            const curve = smoothPath(points);

            if (curve) {
                const area = node(svg, 'path', { d: `${curve} L ${points.at(-1).x} ${baseline} L ${points[0].x} ${baseline} Z`, fill: `url(#${ids[level]})`, class: 'analytics-area' });
                motion(area, [{ opacity: 0 }, { opacity: 1 }], { duration: 700, delay: 450 }, animate);

                const line = node(svg, 'path', { d: curve, pathLength: 1, class: `analytics-line analytics-line-${level}` });
                line.style.strokeDasharray = '1';
                motion(line, [{ strokeDashoffset: 1 }, { strokeDashoffset: 0 }], { duration: 950 }, animate);
            }

            if (months.length <= 14) {
                points.forEach((point, index) => {
                    const dot = node(svg, 'circle', { cx: point.x, cy: point.y, r: 3.5, class: `analytics-point analytics-point-${level}` });
                    motion(dot, [{ opacity: 0, transform: 'scale(0)' }, { opacity: 1, transform: 'scale(1)' }], { duration: 350, delay: 500 + index * 40 }, animate);
                });
            }
        });

        // Crosshair and the highlighted points that follow the pointer or the arrow keys.
        const guide = node(svg, 'line', { y1: geometry.top, y2: baseline, class: 'analytics-guide', visibility: 'hidden' });
        const markers = Object.fromEntries(levels.map((level) => [level, node(svg, 'circle', { r: 6, class: `analytics-marker analytics-marker-${level}`, visibility: 'hidden' })]));

        lineChart.append(svg);
        const tooltip = makeTooltip(lineChart);

        const setActive = (index) => {
            if (index < 0) {
                guide.setAttribute('visibility', 'hidden');
                Object.values(markers).forEach((marker) => marker.setAttribute('visibility', 'hidden'));
                tooltip.hide();
                return;
            }
            const month = months[index];
            guide.setAttribute('x1', x(index));
            guide.setAttribute('x2', x(index));
            guide.setAttribute('visibility', 'visible');
            levels.forEach((level) => {
                markers[level].setAttribute('cx', x(index));
                markers[level].setAttribute('cy', geometry.y(month[level]));
                markers[level].setAttribute('visibility', 'visible');
            });
            const top = Math.min(...levels.map((level) => geometry.y(month[level])));
            const at = toHost(lineChart, svg, x(index), top);
            tooltip.show({
                title: month.label,
                rows: levels.slice().reverse().map((level) => ({
                    color: COLORS[level],
                    label: `${SERIES_LABEL[level]} ${vital.label.toLowerCase()}`,
                    value: numberFormat.format(month[level]),
                })),
            }, at.x, at.y);
        };
        const clear = () => setActive(-1);
        const syncKeyboardIndex = bindKeyboard(svg, months.length, setActive, clear, null);

        const indexAt = (event) => {
            const ratio = (pointerX(svg, event) - geometry.left) / chartWidth;
            return months.length === 1 ? 0 : Math.min(months.length - 1, Math.max(0, Math.round(ratio * (months.length - 1))));
        };
        svg.addEventListener('pointermove', (event) => { const index = indexAt(event); syncKeyboardIndex(index); setActive(index); });
        svg.addEventListener('pointerleave', clear);
    }

    /* ---------- donut chart ---------- */

    const sliceElements = new Map();
    const legendElements = new Map();
    let pieCenter = null;

    function renderPie(items, animate = true) {
        const total = items.reduce((sum, item) => sum + item.count, 0);
        pieTotal.textContent = numberFormat.format(total);
        pieLegend.replaceChildren();
        pieChart.replaceChildren();
        sliceElements.clear();
        legendElements.clear();

        if (!total) {
            showEmpty(pieChart, `No high or low readings to break down for ${period}.`);
            return;
        }

        const active = items.map((item, index) => ({ ...item, color: PIE_COLORS[index % PIE_COLORS.length] })).filter((item) => item.count > 0);
        const size = 240;
        const centre = size / 2;
        const radius = 82;
        const stroke = 30;
        const circumference = 2 * Math.PI * radius;
        const gap = active.length > 1 ? 4 : 0;

        const summary = active.map((item) => `${item.label} ${Math.round(item.count / total * 100)}%`).join(', ');
        const svg = makeSvg(size, size, `Donut chart of abnormal readings by vital sign: ${summary}.`, 'analytics-pie-svg');
        node(svg, 'circle', { cx: centre, cy: centre, r: radius, class: 'analytics-donut-track', 'stroke-width': stroke });

        const group = node(svg, 'g', { transform: `rotate(-90 ${centre} ${centre})` });
        let offset = 0;
        active.forEach((item, index) => {
            const length = circumference * item.count / total;
            const visibleLength = Math.max(0.5, length - gap);
            const slice = node(group, 'circle', {
                cx: centre,
                cy: centre,
                r: radius,
                fill: 'none',
                stroke: item.color,
                'stroke-width': stroke,
                'stroke-dasharray': `${visibleLength} ${circumference - visibleLength}`,
                'stroke-dashoffset': -offset,
                class: 'analytics-slice',
            });
            slice.dataset.key = item.key;
            motion(slice, [{ strokeDasharray: `0 ${circumference}` }, { strokeDasharray: `${visibleLength} ${circumference - visibleLength}` }], { duration: 800, delay: 150 + index * 140 }, animate);
            sliceElements.set(item.key, slice);
            offset += length;
        });

        const value = node(svg, 'text', { x: centre, y: centre + 2, 'text-anchor': 'middle', class: 'analytics-donut-value' }, numberFormat.format(total));
        const label = node(svg, 'text', { x: centre, y: centre + 24, 'text-anchor': 'middle', class: 'analytics-donut-label' }, 'abnormal readings');
        pieCenter = { value, label, total };
        pieChart.append(svg);

        active.forEach((item) => {
            const percent = Math.round(item.count / total * 100);
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'analytics-legend-item';
            button.dataset.key = item.key;
            button.setAttribute('aria-pressed', 'false');
            button.style.setProperty('--legend-color', item.color);
            button.setAttribute('aria-label', `${item.label}: ${numberFormat.format(item.high)} high and ${numberFormat.format(item.low)} low readings, ${percent}% of all abnormal readings. Show it in the line chart.`);

            const swatch = document.createElement('i');
            const copy = document.createElement('span');
            copy.className = 'analytics-legend-copy';
            const name = document.createElement('span');
            name.textContent = item.label;
            const detail = document.createElement('small');
            detail.textContent = `High ${numberFormat.format(item.high)} · Low ${numberFormat.format(item.low)}`;
            const meter = document.createElement('span');
            meter.className = 'analytics-legend-meter';
            const fill = document.createElement('i');
            fill.style.width = `${percent}%`;
            meter.append(fill);
            copy.append(name, detail, meter);
            const share = document.createElement('b');
            share.textContent = `${percent}%`;
            button.append(swatch, copy, share);

            button.addEventListener('click', () => selectVital(item.key));
            button.addEventListener('pointerenter', () => highlightPie(item.key));
            button.addEventListener('pointerleave', () => highlightPie(null));
            button.addEventListener('focus', () => highlightPie(item.key));
            button.addEventListener('blur', () => highlightPie(null));
            pieLegend.append(button);
            legendElements.set(item.key, button);

            const slice = sliceElements.get(item.key);
            slice.addEventListener('pointerenter', () => highlightPie(item.key));
            slice.addEventListener('pointerleave', () => highlightPie(null));
            slice.addEventListener('click', () => selectVital(item.key));
            slice.dataset.percent = String(percent);
            slice.dataset.count = String(item.count);
            slice.dataset.label = item.label;
        });

        markPie(selectedKey);
    }

    /** Hover or focus: fade the other slices and show this slice's numbers in the middle. */
    function highlightPie(key) {
        const svg = pieChart.querySelector('svg');
        if (!svg || !pieCenter) return;
        svg.classList.toggle('is-hovering', key !== null);
        sliceElements.forEach((slice, sliceKey) => slice.classList.toggle('is-active', sliceKey === key));
        legendElements.forEach((button, buttonKey) => button.classList.toggle('is-active', buttonKey === key));

        const slice = key === null ? null : sliceElements.get(key);
        pieCenter.value.textContent = slice ? numberFormat.format(Number(slice.dataset.count)) : numberFormat.format(pieCenter.total);
        pieCenter.label.textContent = slice ? `${slice.dataset.label} · ${slice.dataset.percent}%` : 'abnormal readings';
    }

    /** The vital sign shown in the line chart is outlined in the donut and legend. */
    function markPie(key) {
        sliceElements.forEach((slice, sliceKey) => slice.classList.toggle('is-selected', sliceKey === key));
        legendElements.forEach((button, buttonKey) => {
            button.classList.toggle('is-selected', buttonKey === key);
            button.setAttribute('aria-pressed', String(buttonKey === key));
        });
    }

    /* ---------- cards, people links and wiring ---------- */

    // Links to the Patients page, filtered to the selected vital sign and the chosen period.
    function showPeople(vital) {
        const link = (selector, count, url, phrase) => {
            const el = root.querySelector(selector);
            el.hidden = count === 0;
            el.href = url;
            el.querySelector('span').textContent = `View patients with ${phrase} (${numberFormat.format(count)})`;
        };
        link('[data-people-high]', vital.peopleHigh, vital.high_url, vital.high_phrase);
        link('[data-people-low]', vital.peopleLow, vital.low_url, vital.low_phrase);
        root.querySelector('[data-people-none]').hidden = vital.peopleHigh + vital.peopleLow > 0;
    }

    function selectVital(key, animate = true) {
        const vital = vitals[key];
        if (!vital) return;
        selectedKey = key;
        cards.forEach((card) => {
            const selected = card.dataset.kpi === key;
            card.classList.toggle('is-selected', selected);
            card.setAttribute('aria-pressed', String(selected));
        });
        trendTitle.textContent = vital.label;
        showPeople(vital);
        renderLine(vital, animate);
        markPie(key);
        if (entrancePending) {
            entrancePending = false;
            animateKpiTotals();
            window.setTimeout(() => document.body.classList.remove('adm-dashboard-entrance'), 1900);
        }
    }

    function animateKpiTotals() {
        root.querySelectorAll('[data-kpi-high], [data-kpi-low]').forEach((el) => {
            const target = Number(el.dataset.kpiHigh ?? el.dataset.kpiLow);
            const startTime = performance.now();
            const duration = 900;
            const tick = (now) => {
                const progress = Math.min(1, (now - startTime) / duration);
                const eased = 1 - Math.pow(1 - progress, 4);
                el.textContent = numberFormat.format(Math.round(target * eased));
                if (progress < 1) requestAnimationFrame(tick);
            };
            el.textContent = '0';
            requestAnimationFrame(tick);
        });
    }

    // Show or hide the High / Low line. At least one stays visible.
    seriesButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const level = button.dataset.series;
            if (visible[level] && !Object.entries(visible).some(([other, shown]) => other !== level && shown)) return;
            visible[level] = !visible[level];
            seriesButtons.forEach((b) => b.setAttribute('aria-pressed', String(visible[b.dataset.series])));
            if (selectedKey) renderLine(vitals[selectedKey], false);
        });
    });

    cards.forEach((card) => card.addEventListener('click', () => selectVital(card.dataset.kpi)));

    renderBars(checkups);
    renderPie(pieData);
    const initialKey = cards[0]?.dataset.kpi;
    if (initialKey) selectVital(initialKey);

    let resizeTimer;
    const widths = { bar: barChart.clientWidth, line: lineChart.clientWidth };
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            if (barChart.clientWidth !== widths.bar) {
                widths.bar = barChart.clientWidth;
                renderBars(checkups, false);
            }
            if (lineChart.clientWidth !== widths.line) {
                widths.line = lineChart.clientWidth;
                if (selectedKey) renderLine(vitals[selectedKey], false);
            }
        }, 150);
    });
})();
