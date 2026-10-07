(() => {
    const root = document.querySelector('[data-analytics-dashboard]');
    if (!root) return;

    const vitals = JSON.parse(root.dataset.vitals || '{}');
    const checkups = JSON.parse(root.dataset.checkups || '[]');
    const pieData = JSON.parse(root.dataset.pie || '[]');
    const cards = [...root.querySelectorAll('[data-kpi]')];
    const entranceAnimation = root.dataset.entrance === 'true'
        && !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const trendTitle = root.querySelector('[data-trend-title]');
    const barChart = root.querySelector('[data-bar-chart]');
    const lineChart = root.querySelector('[data-line-chart]');
    const pieChart = root.querySelector('[data-pie-chart]');
    const pieLegend = root.querySelector('[data-pie-legend]');
    const pieTotal = root.querySelector('[data-pie-total]');
    const colors = ['#d9534f', '#e4a321', '#168b7c', '#7958bd'];
    const numberFormat = new Intl.NumberFormat();
    const svgNS = 'http://www.w3.org/2000/svg';
    let entrancePending = entranceAnimation;

    const makeSvg = (label, width) => {
        const svg = document.createElementNS(svgNS, 'svg');
        svg.setAttribute('viewBox', `0 0 ${width} 280`);
        svg.setAttribute('preserveAspectRatio', 'xMidYMid meet');
        svg.setAttribute('aria-hidden', 'true');
        svg.setAttribute('focusable', 'false');
        svg.setAttribute('class', 'analytics-svg');
        svg.setAttribute('data-chart', label);
        return svg;
    };

    const node = (svg, name, attrs = {}, text = null) => {
        const el = document.createElementNS(svgNS, name);
        Object.entries(attrs).forEach(([key, value]) => el.setAttribute(key, String(value)));
        if (text !== null) el.textContent = text;
        svg.append(el);
        return el;
    };

    const showEmpty = (target, message) => {
        target.replaceChildren();
        const empty = document.createElement('p');
        empty.className = 'analytics-empty';
        empty.textContent = message;
        target.append(empty);
    };

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

    function renderBars(months) {
        if (!months.length) return showEmpty(barChart, 'No monthly patient records for this year.');
        const width = Math.max(320, Math.round(barChart.clientWidth));
        const svg = makeSvg('bar', width);
        const left = 42;
        const right = 8;
        const top = 14;
        const chartWidth = width - left - right;
        const chartHeight = 280 - top - 48;
        const maxValue = Math.max(1, ...months.map((month) => month.count));
        const step = chartWidth / months.length;
        const barWidth = Math.min(32, step * 0.58);

        for (let guide = 0; guide <= 4; guide += 1) {
            const y = top + (chartHeight / 4) * guide;
            node(svg, 'line', { x1: left, x2: width - right, y1: y, y2: y, class: 'analytics-gridline' });
            node(svg, 'text', { x: left - 7, y: y + 4, 'text-anchor': 'end', class: 'analytics-axis-label' }, numberFormat.format(Math.round(maxValue * (4 - guide) / 4)));
        }

        months.forEach((month, index) => {
            const x = left + step * index + (step - barWidth) / 2;
            const height = month.count ? Math.max(2, chartHeight * month.count / maxValue) : 0;
            const rect = node(svg, 'rect', {
                x,
                y: top + chartHeight - height,
                width: barWidth,
                height,
                rx: Math.min(4, barWidth / 4),
                class: `analytics-bar${month.count && index === months.reduce((latestIndex, candidate, candidateIndex, series) => (
                    candidate.count > series[latestIndex].count ? candidateIndex : latestIndex
                ), 0) ? ' analytics-bar-latest' : ''}`,
            });
            if (entrancePending) rect.style.animationDelay = `${0.32 + index * 0.035}s`;
            node(rect, 'title', {}, `${month.label}: ${numberFormat.format(month.count)} check-ups`);
            node(svg, 'text', { x: x + barWidth / 2, y: 252, 'text-anchor': 'middle', class: 'analytics-axis-label' }, month.shortLabel);
        });

        barChart.replaceChildren(svg);
    }

    function renderLine(vital) {
        const months = vital.months;
        if (!months.length) return showEmpty(lineChart, 'No vital-sign records for this year.');
        const width = Math.max(320, Math.round(lineChart.clientWidth));
        const svg = makeSvg('line', width);
        const left = 42;
        const right = 8;
        const top = 14;
        const chartWidth = width - left - right;
        const chartHeight = 280 - top - 48;
        const maxValue = Math.max(1, ...months.flatMap((month) => [month.high, month.low]));
        const pointX = (index) => left + (months.length === 1 ? chartWidth / 2 : chartWidth * index / (months.length - 1));
        const pointY = (value) => top + chartHeight - chartHeight * value / maxValue;

        for (let guide = 0; guide <= 4; guide += 1) {
            const y = top + (chartHeight / 4) * guide;
            node(svg, 'line', { x1: left, x2: width - right, y1: y, y2: y, class: 'analytics-gridline' });
            node(svg, 'text', { x: left - 7, y: y + 4, 'text-anchor': 'end', class: 'analytics-axis-label' }, numberFormat.format(Math.round(maxValue * (4 - guide) / 4)));
        }

        ['high', 'low'].forEach((level) => {
            const points = months.map((month, index) => ({
                x: pointX(index),
                y: pointY(month[level]),
            }));
            node(svg, 'path', {
                d: smoothPath(points),
                pathLength: 1,
                class: `analytics-line analytics-line-${level}`,
            });
            months.forEach((month, index) => {
                const circle = node(svg, 'circle', {
                    cx: pointX(index),
                    cy: pointY(month[level]),
                    r: 3,
                    class: `analytics-point analytics-point-${level}`,
                });
                node(circle, 'title', {}, `${month.label}: ${numberFormat.format(month[level])} ${level} readings`);
            });
        });

        months.forEach((month, index) => {
            node(svg, 'text', { x: pointX(index), y: 252, 'text-anchor': 'middle', class: 'analytics-axis-label' }, month.shortLabel);
        });
        lineChart.replaceChildren(svg);
    }

    function renderPie(items) {
        const totalCount = items.reduce((sum, item) => sum + item.count, 0);
        pieTotal.textContent = numberFormat.format(totalCount);
        pieLegend.replaceChildren();
        if (!totalCount) {
            showEmpty(pieChart, 'No high readings to break down for this year.');
            return;
        }

        const svg = document.createElementNS(svgNS, 'svg');
        svg.setAttribute('viewBox', '0 0 240 240');
        svg.setAttribute('aria-hidden', 'true');
        svg.setAttribute('focusable', 'false');
        svg.setAttribute('class', 'analytics-pie-svg');
        const active = items.map((item, index) => ({ ...item, color: colors[index % colors.length] }))
            .filter((item) => item.count > 0);
        let angle = -Math.PI / 2;

        if (active.length === 1) {
            const circle = document.createElementNS(svgNS, 'circle');
            circle.setAttribute('cx', '120');
            circle.setAttribute('cy', '120');
            circle.setAttribute('r', '112');
            circle.setAttribute('fill', active[0].color);
            svg.append(circle);
        } else {
            active.forEach((item) => {
                const slice = item.count / totalCount * Math.PI * 2;
                const end = angle + slice;
                const x1 = 120 + 112 * Math.cos(angle);
                const y1 = 120 + 112 * Math.sin(angle);
                const x2 = 120 + 112 * Math.cos(end);
                const y2 = 120 + 112 * Math.sin(end);
                const path = document.createElementNS(svgNS, 'path');
                path.setAttribute('d', `M 120 120 L ${x1} ${y1} A 112 112 0 ${slice > Math.PI ? 1 : 0} 1 ${x2} ${y2} Z`);
                path.setAttribute('fill', item.color);
                path.setAttribute('class', 'analytics-pie-slice');
                const tooltip = document.createElementNS(svgNS, 'title');
                tooltip.textContent = `${item.label}: ${numberFormat.format(item.count)} abnormal readings, ${numberFormat.format(item.high)} high and ${numberFormat.format(item.low)} low (${Math.round(item.count / totalCount * 100)}%)`;
                path.append(tooltip);
                svg.append(path);
                angle = end;
            });
        }

        pieChart.replaceChildren(svg);

        active.forEach((item) => {
            const row = document.createElement('div');
            row.className = 'analytics-legend-item';
            const swatch = document.createElement('i');
            swatch.style.backgroundColor = item.color;
            const label = document.createElement('span');
            label.textContent = item.label;
            const detail = document.createElement('small');
            detail.textContent = `High ${numberFormat.format(item.high)} · Low ${numberFormat.format(item.low)}`;
            const value = document.createElement('b');
            value.textContent = `${Math.round(item.count / totalCount * 100)}%`;
            const text = document.createElement('span');
            text.className = 'analytics-legend-copy';
            text.append(label, detail);
            row.append(swatch, text, value);
            pieLegend.append(row);
        });
    }

    function selectVital(key) {
        const vital = vitals[key];
        if (!vital) return;
        cards.forEach((card) => {
            const selected = card.dataset.kpi === key;
            card.classList.toggle('is-selected', selected);
            card.setAttribute('aria-pressed', String(selected));
        });
        trendTitle.textContent = vital.label;
        renderLine(vital);
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

    renderBars(checkups);
    renderPie(pieData);
    cards.forEach((card) => card.addEventListener('click', () => selectVital(card.dataset.kpi)));
    const initialKey = cards[0]?.dataset.kpi;
    if (initialKey) selectVital(initialKey);

    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            renderBars(checkups);
            const selected = cards.find((card) => card.getAttribute('aria-pressed') === 'true');
            if (selected) selectVital(selected.dataset.kpi);
        }, 120);
    });
})();
