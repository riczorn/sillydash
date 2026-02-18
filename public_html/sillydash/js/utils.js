/**
 * Dashboard chart and drill-down utilities.
 *
 * Expects two data-attributes on #chartContainer:
 *   data-chart-url   – API endpoint for chart data
 *   data-detail-url  – API endpoint for drill-down detail
 */

// --- helpers ---------------------------------------------------------------

function formattedBytes(d) {
    if (d === 0) return '0 B';
    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(d) / Math.log(1024));
    const unit = units[i];
    const decimals = unit === 'TB' ? 2 : 1;
    return (d / Math.pow(1024, i)).toFixed(decimals) + ' ' + unit;
}

// --- main ------------------------------------------------------------------

document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('chartContainer');
    if (!container) return;           // page has no chart

    const chartUrl = container.dataset.chartUrl;
    const detailUrl = container.dataset.detailUrl;
    const accountFilter = container.dataset.account || '';
    const filter = document.getElementById('daysFilter');
    const resetBtn = document.getElementById('resetZoomBtn');
    let currentData = [];

    resetBtn.addEventListener('click', () => {
        if (currentData.length > 0) renderChart(currentData);
    });

    // ---- data loading -----------------------------------------------------

    function loadChart(days) {
        container.innerHTML = '<p style="text-align:center;padding:2rem">Loading…</p>';
        let url = chartUrl + '?days=' + days;
        if (accountFilter) url += '&account=' + encodeURIComponent(accountFilter);
        fetch(url)
            .then(r => r.json())
            .then(data => {
                currentData = data;
                renderChart(data);
                renderTable(data);
            })
            .catch(err => {
                console.error('Error fetching chart data:', err);
                container.innerHTML = '<p style="text-align:center;color:red">Error loading data</p>';
            });
    }

    // ---- chart rendering --------------------------------------------------

    function renderChart(data) {
        container.innerHTML = '';
        d3.selectAll('.d3-tooltip').remove();

        if (!Array.isArray(data) || data.length === 0) {
            container.innerHTML = '<p style="text-align:center;padding:2rem">No data available.</p>';
            return;
        }

        const margin = { top: 40, right: 80, bottom: 30, left: 80 };
        const width = container.clientWidth - margin.left - margin.right;
        const height = 400 - margin.top - margin.bottom;

        const svg = d3.select('#chartContainer')
            .append('svg')
            .attr('width', width + margin.left + margin.right)
            .attr('height', height + margin.top + margin.bottom)
            .append('g')
            .attr('transform', 'translate(' + margin.left + ',' + margin.top + ')');

        // parse dates
        const parseDate = d3.timeParse('%Y-%m-%d');
        data.forEach(d => { if (!d.dateObj) d.dateObj = parseDate(d.date); });

        const sumstat = d3.group(data, d => d.type);
        const leftKeys = ['accounts', 'mail'];
        const rightKeys = ['db', 'spam', 'log'];

        const colorMap = {
            accounts: '#ff9800',
            mail: '#f44336',
            db: '#2196f3',
            spam: '#00bcd4',
            log: '#9c27b0'
        };

        // scales
        const x = d3.scaleTime()
            .domain(d3.extent(data, d => d.dateObj))
            .range([0, width]);

        const maxLeft = d3.max(data.filter(d => leftKeys.includes(d.type)), d => d.value) || 0;
        const yLeft = d3.scaleLinear().domain([0, maxLeft * 1.1]).range([height, 0]);

        const maxRight = d3.max(data.filter(d => rightKeys.includes(d.type)), d => d.value) || 0;
        const yRight = d3.scaleLinear().domain([0, maxRight * 1.25]).range([height, 0]);

        // clip
        svg.append('defs').append('clipPath')
            .attr('id', 'clip')
            .append('rect').attr('width', width).attr('height', height);

        // axes
        const xAxis = svg.append('g')
            .attr('transform', 'translate(0,' + height + ')')
            .call(d3.axisBottom(x));

        svg.append('g')
            .call(d3.axisLeft(yLeft).tickFormat(formattedBytes))
            .style('color', '#ff7043');

        svg.append('g')
            .attr('transform', 'translate(' + width + ',0)')
            .call(d3.axisRight(yRight).tickFormat(formattedBytes))
            .style('color', '#29b6f6');

        // smooth line generator
        const lineGen = (scale) => d3.line()
            .curve(d3.curveMonotoneX)
            .x(d => x(d.dateObj))
            .y(d => scale(d.value));

        const chartBody = svg.append('g').attr('clip-path', 'url(#clip)');

        // draw lines
        chartBody.selectAll('.line')
            .data(sumstat)
            .join('path')
            .attr('class', 'line')
            .attr('fill', 'none')
            .attr('stroke', d => colorMap[d[0]])
            .attr('stroke-width', 2)
            .attr('d', d => {
                const scale = leftKeys.includes(d[0]) ? yLeft : yRight;
                return lineGen(scale)(d[1]);
            });

        // ---- tooltip ------------------------------------------------------

        const tooltip = d3.select('body').append('div').attr('class', 'd3-tooltip');

        const verticalLine = svg.append('line')
            .attr('class', 'vertical-line')
            .attr('y1', 0).attr('y2', height)
            .attr('stroke', 'var(--text-muted)')
            .attr('stroke-width', 1)
            .attr('stroke-dasharray', '4,4')
            .style('opacity', 0)
            .style('pointer-events', 'none');

        const uniqueDates = Array.from(d3.group(data, d => d.date).keys()).sort();
        const dataByDate = d3.group(data, d => d.date);

        function onMouseMove(event) {
            const pointer = d3.pointer(event);
            const hoverDate = x.invert(pointer[0]);
            const bisect = d3.bisector(d => new Date(d)).left;
            const index = bisect(uniqueDates, hoverDate, 1);
            const d0 = new Date(uniqueDates[index - 1]);
            const d1 = uniqueDates[index] ? new Date(uniqueDates[index]) : d0;
            const closest = (hoverDate - d0 > d1 - hoverDate) ? uniqueDates[index] : uniqueDates[index - 1];
            if (!closest) return;

            const entries = dataByDate.get(closest);
            const xPos = x(parseDate(closest));

            verticalLine
                .attr('x1', xPos).attr('x2', xPos)
                .attr('y1', 0).attr('y2', height)
                .style('opacity', 0.5);

            let html = '<strong>' + closest + '</strong><br/>';
            entries.sort((a, b) => b.value - a.value);
            entries.forEach(d => {
                html += '<div style="display:flex;justify-content:space-between;align-items:center;margin-top:4px">'
                    + '<span style="display:inline-block;width:8px;height:8px;background:' + colorMap[d.type] + ';margin-right:6px"></span>'
                    + '<span style="margin-right:8px">' + d.type.charAt(0).toUpperCase() + d.type.slice(1) + ':</span>'
                    + '<strong>' + formattedBytes(d.value) + '</strong>'
                    + '</div>';
            });

            let left = event.pageX + 15;
            if (left + 160 > window.innerWidth) left = event.pageX - 175;

            tooltip.html(html)
                .style('left', left + 'px')
                .style('top', (event.pageY - 20) + 'px')
                .style('opacity', 1);
        }

        function onMouseOut() {
            tooltip.style('opacity', 0);
            verticalLine.style('opacity', 0);
        }

        // ---- zoom / brush -------------------------------------------------

        function updateChart(event) {
            const sel = event.selection;
            if (!sel) return;
            const [x0, x1] = sel.map(x.invert);
            x.domain([x0, x1]);

            xAxis.transition().duration(800).call(d3.axisBottom(x));
            chartBody.selectAll('.line').transition().duration(800)
                .attr('d', d => {
                    const scale = leftKeys.includes(d[0]) ? yLeft : yRight;
                    return lineGen(scale)(d[1]);
                });
            brushGroup.call(brush.move, null);
        }

        const brush = d3.brushX().extent([[0, 0], [width, height]]).on('end', updateChart);
        const brushGroup = chartBody.append('g').attr('class', 'brush').call(brush);

        // Attach tooltip + click to brush overlay
        brushGroup.select('.overlay')
            .on('mousemove', onMouseMove)
            .on('mouseout', onMouseOut)
            .on('click', function (event) {
                const pointer = d3.pointer(event);
                const hoverDate = x.invert(pointer[0]);
                const bisect = d3.bisector(d => new Date(d)).left;
                const idx = bisect(uniqueDates, hoverDate, 1);
                const d0 = new Date(uniqueDates[idx - 1]);
                const d1 = uniqueDates[idx] ? new Date(uniqueDates[idx]) : d0;
                const closest = (hoverDate - d0 > d1 - hoverDate) ? uniqueDates[idx] : uniqueDates[idx - 1];
                if (closest) {
                    window.openDrillDown({ date: closest, type: 'accounts' });
                }
            });

        // double-click reset
        svg.on('dblclick', () => {
            x.domain(d3.extent(data, d => d.dateObj));
            xAxis.transition().call(d3.axisBottom(x));
            chartBody.selectAll('.line').transition().attr('d', d => {
                const scale = leftKeys.includes(d[0]) ? yLeft : yRight;
                return lineGen(scale)(d[1]);
            });
            brushGroup.call(brush.move, null);
        });

        // ---- legend -------------------------------------------------------

        const legendW = 100;
        const legendX = (width - sumstat.size * legendW) / 2;
        const legendG = svg.append('g').attr('transform', 'translate(' + legendX + ',-15)');
        [...sumstat.keys()].forEach((key, i) => {
            const g = legendG.append('g').attr('transform', 'translate(' + (i * legendW) + ',0)');
            g.append('rect').attr('width', 10).attr('height', 10).attr('fill', colorMap[key]);
            g.append('text').attr('x', 15).attr('y', 10).text(key)
                .style('font-size', '12px').style('fill', 'var(--text-muted)');
        });
    }

    // ---- drill-down -------------------------------------------------------

    window.openDrillDown = function (d) {
        var modal = document.getElementById('drillDownModal');
        var content = document.getElementById('drillDownContent');
        document.getElementById('drillDownTitle').innerText =
            'Details: ' + d.type.toUpperCase() + ' on ' + d.date;
        modal.style.display = 'block';
        content.innerHTML = '<p>Loading…</p>';

        fetch(detailUrl + '?date=' + d.date + '&type=' + d.type)
            .then(r => r.json())
            .then(details => {
                if (details.error) { content.innerHTML = '<p style="color:red">' + details.error + '</p>'; return; }
                if (details.length === 0) { content.innerHTML = '<p>No details available.</p>'; return; }
                var html = '<table style="width:100%;text-align:left"><thead><tr><th>Domain</th><th>Size</th></tr></thead><tbody>';
                details.forEach(item => {
                    html += '<tr><td>' + item.domain + '</td><td>' + formattedBytes(item.size) + '</td></tr>';
                });
                html += '</tbody></table>';
                content.innerHTML = html;
            })
            .catch(() => { content.innerHTML = '<p style="color:red">Error loading details.</p>'; });
    };

    window.closeDrillDown = function () {
        document.getElementById('drillDownModal').style.display = 'none';
    };

    window.addEventListener('click', function (e) {
        var modal = document.getElementById('drillDownModal');
        if (e.target === modal) modal.style.display = 'none';
    });

    // ---- data table -------------------------------------------------------

    function renderTable(data) {
        var tb = document.querySelector('#debugTable tbody');
        if (!tb) return;
        tb.innerHTML = '';
        if (!data || data.length === 0) {
            tb.innerHTML = '<tr><td colspan="6" style="text-align:center">No data available</td></tr>';
            return;
        }
        var byDate = d3.group(data, d => d.date);
        Array.from(byDate.keys()).sort().reverse().forEach(dateStr => {
            var entries = byDate.get(dateStr);
            var row = document.createElement('tr');
            var val = type => { var f = entries.find(e => e.type === type); return f ? formattedBytes(f.value) : '-'; };
            row.innerHTML = '<td>' + dateStr + '</td>'
                + '<td>' + val('accounts') + '</td>'
                + '<td>' + val('mail') + '</td>'
                + '<td>' + val('db') + '</td>'
                + '<td>' + val('spam') + '</td>'
                + '<td>' + val('log') + '</td>';
            tb.appendChild(row);
        });
    }

    // ---- init -------------------------------------------------------------

    loadChart(filter.value);
    filter.addEventListener('change', function () {
        loadChart(this.value);
        if (subContainer) loadSubdomainChart(this.value);
    });
    window.addEventListener('resize', function () {
        loadChart(filter.value);
        if (subContainer) loadSubdomainChart(filter.value);
    });

    // ---- subdomain stacked chart ------------------------------------------

    const subContainer = document.getElementById('subdomainChartContainer');

    if (subContainer) {
        const subUrl = subContainer.dataset.subdomainUrl;
        const subAccount = subContainer.dataset.account;

        function loadSubdomainChart(days) {
            subContainer.innerHTML = '<p style="text-align:center;padding:2rem">Loading…</p>';
            let url = subUrl + '?days=' + days + '&account=' + encodeURIComponent(subAccount);
            fetch(url)
                .then(r => r.json())
                .then(data => renderStackedChart(data))
                .catch(err => {
                    console.error('Subdomain chart error:', err);
                    subContainer.innerHTML = '<p style="text-align:center;color:red">Error loading subdomain data</p>';
                });
        }

        function renderStackedChart(data) {
            subContainer.innerHTML = '';
            const parentCard = subContainer.closest('.card');
            if (!data || data.length === 0) {
                if (parentCard) parentCard.style.display = 'none';
                return;
            }
            if (parentCard) parentCard.style.display = '';

            const margin = { top: 20, right: 30, bottom: 30, left: 70 };
            const w = subContainer.clientWidth;
            const h = subContainer.clientHeight;
            const width = w - margin.left - margin.right;
            const height = h - margin.top - margin.bottom;

            const svg = d3.select(subContainer).append('svg')
                .attr('width', w).attr('height', h)
                .append('g').attr('transform', `translate(${margin.left},${margin.top})`);

            // Build pivoted data: [{date, sub1: val, sub2: val, ...}, ...]
            const subdomains = [...new Set(data.map(d => d.subdomain))];
            const byDate = d3.group(data, d => d.date);
            const pivoted = [];
            for (const [date, rows] of byDate) {
                const obj = { date: new Date(date) };
                subdomains.forEach(s => obj[s] = 0);
                rows.forEach(r => obj[r.subdomain] = r.value);
                pivoted.push(obj);
            }
            pivoted.sort((a, b) => a.date - b.date);

            const stack = d3.stack().keys(subdomains).order(d3.stackOrderDescending);
            const series = stack(pivoted);

            const x = d3.scaleTime()
                .domain(d3.extent(pivoted, d => d.date))
                .range([0, width]);

            const y = d3.scaleLinear()
                .domain([0, d3.max(series, s => d3.max(s, d => d[1])) * 1.05])
                .range([height, 0]);

            const palette = [
                '#6366f1', '#06b6d4', '#10b981', '#f59e0b', '#ef4444',
                '#8b5cf6', '#ec4899', '#14b8a6', '#f97316', '#84cc16',
                '#a78bfa', '#22d3ee', '#34d399', '#fbbf24', '#fb7185'
            ];
            const color = d3.scaleOrdinal().domain(subdomains).range(palette);

            const area = d3.area()
                .x(d => x(d.data.date))
                .y0(d => y(d[0]))
                .y1(d => y(d[1]))
                .curve(d3.curveMonotoneX);

            // Draw areas
            svg.selectAll('.layer')
                .data(series)
                .join('path')
                .attr('class', 'layer')
                .attr('d', area)
                .attr('fill', d => color(d.key))
                .attr('opacity', 0.8);

            // Axes
            svg.append('g').attr('transform', `translate(0,${height})`)
                .call(d3.axisBottom(x).ticks(6));

            svg.append('g').call(d3.axisLeft(y).tickFormat(formattedBytes));

            // Tooltip
            const tooltip = d3.select(subContainer).append('div')
                .style('position', 'absolute')
                .style('background', 'rgba(0,0,0,0.85)')
                .style('color', '#fff')
                .style('padding', '10px 14px')
                .style('border-radius', '6px')
                .style('font-size', '12px')
                .style('pointer-events', 'none')
                .style('opacity', 0)
                .style('z-index', 10)
                .style('max-width', '300px');

            const hoverLine = svg.append('line')
                .attr('stroke', '#888').attr('stroke-width', 1)
                .attr('stroke-dasharray', '4,3')
                .attr('y1', 0).attr('y2', height)
                .style('opacity', 0);

            // Hover overlay
            svg.append('rect')
                .attr('width', width).attr('height', height)
                .attr('fill', 'transparent')
                .on('mousemove', function (event) {
                    const [mx] = d3.pointer(event);
                    const dateHover = x.invert(mx);
                    // Find closest date
                    const bisect = d3.bisector(d => d.date).left;
                    let idx = bisect(pivoted, dateHover, 1);
                    if (idx >= pivoted.length) idx = pivoted.length - 1;
                    if (idx > 0) {
                        const d0 = pivoted[idx - 1], d1 = pivoted[idx];
                        idx = (dateHover - d0.date > d1.date - dateHover) ? idx : idx - 1;
                    }
                    const row = pivoted[idx];
                    const xPos = x(row.date);
                    hoverLine.attr('x1', xPos).attr('x2', xPos).style('opacity', 1);

                    // Build tooltip
                    let html = '<strong>' + row.date.toISOString().slice(0, 10) + '</strong>'
                        + '<table style="border-spacing:4px 2px;margin-top:4px">';
                    const entries = subdomains
                        .map(s => ({ name: s, val: row[s] }))
                        .filter(e => e.val > 0)
                        .sort((a, b) => b.val - a.val);
                    entries.forEach(e => {
                        html += '<tr><td><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:'
                            + color(e.name) + ';margin-right:5px"></span>' + e.name
                            + '</td><td style="text-align:right;padding-left:12px">' + formattedBytes(e.val) + '</td></tr>';
                    });
                    html += '</table>';

                    tooltip.html(html).style('opacity', 1);

                    // Position tooltip
                    const rect = subContainer.getBoundingClientRect();
                    let left = mx + margin.left + 15;
                    if (left + 200 > w) left = mx + margin.left - 220;
                    tooltip.style('left', left + 'px').style('top', (margin.top + 10) + 'px');
                })
                .on('mouseleave', function () {
                    tooltip.style('opacity', 0);
                    hoverLine.style('opacity', 0);
                });

            // Legend
            const legend = d3.select(subContainer).append('div')
                .style('display', 'flex').style('flex-wrap', 'wrap')
                .style('gap', '8px 16px').style('padding', '8px 0 0 ' + margin.left + 'px')
                .style('font-size', '12px');
            subdomains.forEach(s => {
                legend.append('span').html(
                    '<span style="display:inline-block;width:12px;height:12px;border-radius:2px;background:'
                    + color(s) + ';margin-right:4px;vertical-align:middle"></span>' + s
                );
            });
        }

        loadSubdomainChart(filter.value);
    }
});
