(() => {
    const root = document.querySelector('[data-analytics-dashboard]');
    if (!root) return;

    const metrics = JSON.parse(root.dataset.metrics || '[]');
    const cards = [...root.querySelectorAll('[data-kpi]')];
    const entranceAnimation = root.dataset.entrance === 'true'
        && !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const title = root.querySelector('[data-chart-title]');
    const trendTitle = root.querySelector('[data-trend-title]');
    const total = root.querySelector('[data-chart-total]');
    const barChart = root.querySelector('[data-bar-chart]');
    const lineChart = root.querySelector('[data-line-chart]');
    const pieChart = root.querySelector('[data-pie-chart]');
    const pieLegend = root.querySelector('[data-pie-legend]');
    const colors = ['#244b91', '#14867d', '#c27a24', '#7959a5', '#3478a8', '#bd5364', '#629447', '#6c7a89'];
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

    function renderBars(months) {
        if (!months.length) return showEmpty(barChart, 'No monthly data for this date range.');
        const width = Math.max(320, Math.round(barChart.clientWidth));
        const svg = makeSvg('bar', width);
        const left = 46;
        const right = 12;
        const top = 20;
        const bottom = 48;
        const chartWidth = width - left - right;
        const chartHeight = 280 - top - bottom;
        const maxValue = Math.max(1, ...months.map((month) => month.count));
        const step = chartWidth / months.length;
        const barWidth = Math.min(34, Math.max(5, step * 0.58));

        for (let guide = 0; guide <= 4; guide += 1) {
            const y = top + (chartHeight / 4) * guide;
            const value = Math.round(maxValue * (4 - guide) / 4);
            node(svg, 'line', { x1: left, x2: width - right, y1: y, y2: y, class: 'analytics-gridline' });
            node(svg, 'text', { x: left - 8, y: y + 4, 'text-anchor': 'end', class: 'analytics-axis-label' }, numberFormat.format(value));
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
                class: 'analytics-bar',
            });
            if (entrancePending) rect.style.animationDelay = `${0.34 + index * 0.035}s`;
            const tip = node(rect, 'title', {}, `${month.label}: ${numberFormat.format(month.count)}`);
            tip.textContent = `${month.label}: ${numberFormat.format(month.count)}`;
            node(svg, 'text', { x: x + barWidth / 2, y: 252, 'text-anchor': 'middle', class: 'analytics-axis-label' }, month.shortLabel);
        });

        barChart.replaceChildren(svg);
    }

    function renderLine(months) {
        if (!months.length) return showEmpty(lineChart, 'No monthly data for this date range.');
        const width = Math.max(320, Math.round(lineChart.clientWidth));
        const svg = makeSvg('line', width);
        const left = 46;
        const right = 12;
        const top = 20;
        const bottom = 48;
        const chartWidth = width - left - right;
        const height = 280 - top - bottom;
        const maxValue = Math.max(1, ...months.map((month) => month.count));
        const pointX = (index) => left + (months.length === 1 ? chartWidth / 2 : chartWidth * index / (months.length - 1));
        const pointY = (value) => top + height - height * value / maxValue;

        for (let guide = 0; guide <= 4; guide += 1) {
            const y = top + (height / 4) * guide;
            node(svg, 'line', { x1: left, x2: width - right, y1: y, y2: y, class: 'analytics-gridline' });
            node(svg, 'text', { x: left - 8, y: y + 4, 'text-anchor': 'end', class: 'analytics-axis-label' }, numberFormat.format(Math.round(maxValue * (4 - guide) / 4)));
        }

        const points = months.map((month, index) => `${pointX(index)},${pointY(month.count)}`).join(' ');
        node(svg, 'polyline', { points, pathLength: 1, class: 'analytics-line' });
        months.forEach((month, index) => {
            const x = pointX(index);
            const y = pointY(month.count);
            const circle = node(svg, 'circle', { cx: x, cy: y, r: months.length > 24 ? 2.5 : 4, class: 'analytics-point' });
            node(circle, 'title', {}, `${month.label}: ${numberFormat.format(month.count)}`);
            node(svg, 'text', { x, y: 252, 'text-anchor': 'middle', class: 'analytics-axis-label' }, month.shortLabel);
        });

        lineChart.replaceChildren(svg);
    }

    function renderPie(months) {
        const totalCount = months.reduce((sum, month) => sum + month.count, 0);
        pieLegend.replaceChildren();
        if (!totalCount) {
            showEmpty(pieChart, 'No activity to break down for this period.');
            return;
        }

        const svg = document.createElementNS(svgNS, 'svg');
        svg.setAttribute('viewBox', '0 0 220 220');
        svg.setAttribute('aria-hidden', 'true');
        svg.setAttribute('focusable', 'false');
        svg.setAttribute('class', 'analytics-pie-svg');
        const active = months.map((month, index) => ({ ...month, color: colors[index % colors.length] }))
            .filter((month) => month.count > 0);
        let angle = -Math.PI / 2;

        if (active.length === 1) {
            const circle = document.createElementNS(svgNS, 'circle');
            circle.setAttribute('cx', '110');
            circle.setAttribute('cy', '110');
            circle.setAttribute('r', '94');
            circle.setAttribute('fill', active[0].color);
            svg.append(circle);
        } else {
            active.forEach((month) => {
                const slice = month.count / totalCount * Math.PI * 2;
                const end = angle + slice;
                const x1 = 110 + 94 * Math.cos(angle);
                const y1 = 110 + 94 * Math.sin(angle);
                const x2 = 110 + 94 * Math.cos(end);
                const y2 = 110 + 94 * Math.sin(end);
                const path = document.createElementNS(svgNS, 'path');
                path.setAttribute('d', `M 110 110 L ${x1} ${y1} A 94 94 0 ${slice > Math.PI ? 1 : 0} 1 ${x2} ${y2} Z`);
                path.setAttribute('fill', month.color);
                path.setAttribute('class', 'analytics-pie-slice');
                const tooltip = document.createElementNS(svgNS, 'title');
                tooltip.textContent = `${month.label}: ${numberFormat.format(month.count)} (${Math.round(month.count / totalCount * 100)}%)`;
                path.append(tooltip);
                svg.append(path);
                angle = end;
            });
        }

        const hole = document.createElementNS(svgNS, 'circle');
        hole.setAttribute('cx', '110');
        hole.setAttribute('cy', '110');
        hole.setAttribute('r', '57');
        hole.setAttribute('class', 'analytics-pie-hole');
        svg.append(hole);
        const centerTotal = document.createElementNS(svgNS, 'text');
        centerTotal.setAttribute('x', '110');
        centerTotal.setAttribute('y', '106');
        centerTotal.setAttribute('text-anchor', 'middle');
        centerTotal.setAttribute('class', 'analytics-pie-total');
        centerTotal.textContent = numberFormat.format(totalCount);
        svg.append(centerTotal);
        const centerLabel = document.createElementNS(svgNS, 'text');
        centerLabel.setAttribute('x', '110');
        centerLabel.setAttribute('y', '126');
        centerLabel.setAttribute('text-anchor', 'middle');
        centerLabel.setAttribute('class', 'analytics-pie-caption');
        centerLabel.textContent = 'TOTAL';
        svg.append(centerLabel);
        pieChart.replaceChildren(svg);

        active.forEach((month) => {
            const item = document.createElement('div');
            item.className = 'analytics-legend-item';
            const swatch = document.createElement('i');
            swatch.style.backgroundColor = month.color;
            const label = document.createElement('span');
            label.textContent = month.label;
            const value = document.createElement('b');
            value.textContent = `${numberFormat.format(month.count)} · ${Math.round(month.count / totalCount * 100)}%`;
            item.append(swatch, label, value);
            pieLegend.append(item);
        });
    }

    function selectMetric(key) {
        const metric = metrics.find((item) => item.key === key);
        if (!metric) return;
        cards.forEach((card) => {
            const selected = card.dataset.kpi === key;
            card.classList.toggle('is-selected', selected);
            card.setAttribute('aria-pressed', String(selected));
        });
        title.textContent = metric.label;
        trendTitle.textContent = metric.label;
        total.textContent = `${numberFormat.format(metric.total)} total`;
        renderBars(metric.months);
        renderLine(metric.months);
        renderPie(metric.months);
        if (entrancePending) {
            entrancePending = false;
            animateKpiTotals();
            window.setTimeout(() => document.body.classList.remove('adm-dashboard-entrance'), 1900);
        }
    }

    function animateKpiTotals() {
        root.querySelectorAll('[data-kpi-total]').forEach((el) => {
            const target = Number(el.dataset.kpiTotal);
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

    cards.forEach((card) => card.addEventListener('click', () => selectMetric(card.dataset.kpi)));
    if (metrics.length) selectMetric(metrics[0].key);

    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            const selected = cards.find((card) => card.getAttribute('aria-pressed') === 'true');
            if (selected) selectMetric(selected.dataset.kpi);
        }, 120);
    });
})();
