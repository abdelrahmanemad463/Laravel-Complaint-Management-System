import './bootstrap';

import Chart from 'chart.js/auto';

const themeToggles = document.querySelectorAll('[data-theme-toggle]');
const themeColorMeta = document.querySelector('[data-theme-color]');

const applyTheme = (theme) => {
    const normalizedTheme = theme === 'dark' ? 'dark' : 'light';
    const isDark = normalizedTheme === 'dark';
    document.documentElement.dataset.theme = normalizedTheme;
    document.documentElement.style.colorScheme = normalizedTheme;
    if (themeColorMeta) themeColorMeta.content = isDark ? '#0f172a' : '#4f46e5';
    if (!themeToggles.length) return;
    themeToggles.forEach((toggle) => {
        toggle.setAttribute('aria-pressed', isDark ? 'true' : 'false');
        toggle.setAttribute('aria-label', toggle.dataset.themeSwitcher || toggle.getAttribute('aria-label') || 'Switch theme');
        const icon = toggle.querySelector('[data-theme-icon]');
        const label = toggle.querySelector('[data-theme-label]');
        if (icon) icon.textContent = isDark ? '☀' : '☾';
        if (label) label.textContent = isDark ? toggle.dataset.lightLabel : toggle.dataset.darkLabel;
    });
};

applyTheme(document.documentElement.dataset.theme || 'light');

themeToggles.forEach((toggle) => {
    toggle.addEventListener('click', () => {
        const nextTheme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
        applyTheme(nextTheme);
        window.localStorage.setItem('complaint-theme', nextTheme);
    });
});

const customerPicker = document.querySelector('[data-customer-picker]');

if (customerPicker?.hasAttribute('data-filter-dropdown')) {
    const trigger = customerPicker.querySelector('[data-picker-trigger]');
    const searchInput = customerPicker.querySelector('#customer-search');
    const hiddenInput = customerPicker.querySelector('#customer_id');
    const summary = customerPicker.querySelector('#customer-summary');
    const results = customerPicker.querySelector('#customer-results');
    const options = customerPicker.querySelector('[data-picker-options]');
    const clearButton = customerPicker.querySelector('[data-picker-clear]');
    const searchUrl = customerPicker.dataset.searchUrl;
    const emptyText = customerPicker.dataset.emptyText;
    const selectLabel = customerPicker.dataset.selectLabel;
    const initialCustomers = [...options.querySelectorAll('.customer-option')].map((button) => ({
        id: button.dataset.id,
        name: button.dataset.name,
        phone_primary: button.dataset.phone,
    }));
    let timer;
    let controller;

    const close = () => {
        results.classList.add('hidden');
        trigger.setAttribute('aria-expanded', 'false');
    };

    const open = () => {
        results.classList.remove('hidden');
        trigger.setAttribute('aria-expanded', 'true');
        searchInput.focus();
    };

    const syncOptions = () => {
        options.querySelectorAll('.customer-option').forEach((button) => {
            const selected = button.dataset.id === hiddenInput.value;
            button.dataset.selected = selected ? '1' : '0';
            button.setAttribute('aria-selected', selected ? 'true' : 'false');
            button.classList.toggle('bg-indigo-100', selected);
            button.classList.toggle('text-indigo-800', selected);
            const check = button.querySelector('.customer-check');
            if (check) check.textContent = selected ? '✓' : '';
        });
        clearButton.classList.toggle('hidden', !hiddenInput.value);
    };

    const setSelected = (button) => {
        hiddenInput.value = button.dataset.id;
        summary.textContent = `${button.dataset.name} — ${button.dataset.phone}`;
        summary.classList.remove('text-slate-500');
        summary.classList.add('text-slate-900');
        syncOptions();
        close();
    };

    const bindOptions = () => {
        options.querySelectorAll('.customer-option').forEach((button) => {
            button.addEventListener('click', () => setSelected(button));
        });
        syncOptions();
    };

    const renderCustomers = (customers) => {
        options.replaceChildren();
        if (!customers.length) {
            const empty = document.createElement('p');
            empty.className = 'px-3 py-3 text-sm text-slate-500';
            empty.textContent = emptyText;
            options.appendChild(empty);
            syncOptions();
            return;
        }
        customers.slice(0, 10).forEach((customer) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'customer-option flex w-full items-center justify-between gap-3 rounded-md px-3 py-2 text-start text-sm hover:bg-indigo-50';
            button.dataset.id = customer.id;
            button.dataset.name = customer.name;
            button.dataset.phone = customer.phone_primary;
            button.setAttribute('role', 'option');
            const name = document.createElement('span');
            name.className = 'min-w-0 truncate font-medium';
            name.textContent = customer.name;
            const meta = document.createElement('span');
            meta.className = 'flex shrink-0 items-center gap-2 text-xs text-slate-500';
            const phone = document.createElement('span');
            phone.textContent = customer.phone_primary;
            const check = document.createElement('span');
            check.className = 'customer-check text-indigo-600';
            check.setAttribute('aria-hidden', 'true');
            meta.append(phone, check);
            button.append(name, meta);
            options.appendChild(button);
        });
        bindOptions();
    };

    const searchCustomers = async () => {
        const term = searchInput.value.trim();
        if (!term) {
            renderCustomers(initialCustomers);
            return;
        }
        controller?.abort();
        controller = new AbortController();
        try {
            const url = new URL(searchUrl, window.location.origin);
            url.searchParams.set('q', term);
            const response = await fetch(url, { headers: { Accept: 'application/json' }, signal: controller.signal });
            if (!response.ok) throw new Error('Customer search failed');
            renderCustomers(await response.json());
        } catch (error) {
            if (error.name !== 'AbortError') console.error(error);
        }
    };

    trigger.addEventListener('click', () => {
        if (results.classList.contains('hidden')) open();
        else close();
    });
    clearButton.addEventListener('click', () => {
        hiddenInput.value = '';
        summary.textContent = selectLabel;
        summary.classList.remove('text-slate-900');
        summary.classList.add('text-slate-500');
        searchInput.value = '';
        renderCustomers(initialCustomers);
        close();
    });
    document.addEventListener('click', (event) => {
        if (!customerPicker.contains(event.target)) close();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') close();
    });
    searchInput.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(searchCustomers, 250);
    });
    bindOptions();
} else if (customerPicker) {
    const searchInput = customerPicker.querySelector('#customer-search');
    const hiddenInput = customerPicker.querySelector('#customer_id');
    const selectedCustomer = customerPicker.querySelector('#selected-customer');
    const results = customerPicker.querySelector('#customer-results');
    const searchUrl = customerPicker.dataset.searchUrl;
    const emptyText = customerPicker.dataset.emptyText;
    let timer;
    let controller;

    const setSelected = (button) => {
        hiddenInput.value = button.dataset.id;
        selectedCustomer.textContent = `${button.dataset.name} — ${button.dataset.phone}`;
        selectedCustomer.classList.remove('hidden');
        results.querySelectorAll('.customer-option').forEach((option) => option.classList.remove('bg-indigo-100', 'text-indigo-800'));
        button.classList.add('bg-indigo-100', 'text-indigo-800');
    };

    const bindOptions = () => {
        results.querySelectorAll('.customer-option').forEach((button) => {
            button.addEventListener('click', () => setSelected(button));
            if (button.dataset.id === hiddenInput.value) button.classList.add('bg-indigo-100', 'text-indigo-800');
        });
    };

    const renderCustomers = (customers) => {
        results.replaceChildren();
        if (!customers.length) {
            const empty = document.createElement('p');
            empty.className = 'px-3 py-3 text-sm text-slate-500';
            empty.textContent = emptyText;
            results.appendChild(empty);
            return;
        }
        customers.slice(0, 10).forEach((customer) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'customer-option flex w-full items-center justify-between rounded-md px-3 py-2 text-start text-sm hover:bg-indigo-50';
            button.dataset.id = customer.id;
            button.dataset.name = customer.name;
            button.dataset.phone = customer.phone_primary;
            const name = document.createElement('span');
            name.className = 'font-medium';
            name.textContent = customer.name;
            const phone = document.createElement('span');
            phone.className = 'text-xs text-slate-500';
            phone.textContent = customer.phone_primary;
            button.append(name, phone);
            results.appendChild(button);
        });
        bindOptions();
    };

    const searchCustomers = async () => {
        const term = searchInput.value.trim();
        if (!term) {
            results.querySelectorAll('.customer-option').forEach((option) => option.classList.remove('hidden'));
            results.querySelectorAll('.customer-option:nth-child(n+11)').forEach((option) => option.classList.add('hidden'));
            bindOptions();
            return;
        }
        controller?.abort();
        controller = new AbortController();
        try {
            const url = new URL(searchUrl, window.location.origin);
            url.searchParams.set('q', term);
            const response = await fetch(url, { headers: { Accept: 'application/json' }, signal: controller.signal });
            if (!response.ok) throw new Error('Customer search failed');
            renderCustomers(await response.json());
        } catch (error) {
            if (error.name !== 'AbortError') console.error(error);
        }
    };

    bindOptions();
    searchInput.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(searchCustomers, 250);
    });
}


const branchPicker = document.querySelector('[data-branch-picker]');

if (branchPicker?.hasAttribute('data-filter-dropdown')) {
    const trigger = branchPicker.querySelector('[data-picker-trigger]');
    const searchInput = branchPicker.querySelector('#branch-search');
    const results = branchPicker.querySelector('#branch-results');
    const options = branchPicker.querySelector('[data-picker-options]');
    const selectedInputs = branchPicker.querySelector('#selected-branch-inputs');
    const summary = branchPicker.querySelector('#branch-summary');
    const clearButton = branchPicker.querySelector('[data-picker-clear]');
    const searchUrl = branchPicker.dataset.searchUrl;
    const emptyText = branchPicker.dataset.emptyText;
    const selectLabel = branchPicker.dataset.selectLabel;
    const singularLabel = branchPicker.dataset.singularLabel;
    const pluralLabel = branchPicker.dataset.pluralLabel;
    const initialBranches = [...options.querySelectorAll('.branch-option')].map((button) => ({ id: button.dataset.id, name: button.dataset.name }));
    const selectedIds = new Set((branchPicker.dataset.selectedIds || '').split(',').filter(Boolean));
    let timer;
    let controller;

    const close = () => {
        results.classList.add('hidden');
        trigger.setAttribute('aria-expanded', 'false');
    };

    const open = () => {
        results.classList.remove('hidden');
        trigger.setAttribute('aria-expanded', 'true');
        searchInput.focus();
    };

    const syncSelectedInputs = () => {
        selectedInputs.replaceChildren();
        selectedIds.forEach((id) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'branch_ids[]';
            input.value = id;
            selectedInputs.appendChild(input);
        });
    };

    const syncButtons = () => {
        options.querySelectorAll('.branch-option').forEach((button) => {
            const selected = selectedIds.has(button.dataset.id);
            button.dataset.selected = selected ? '1' : '0';
            button.setAttribute('aria-selected', selected ? 'true' : 'false');
            button.classList.toggle('bg-indigo-100', selected);
            button.classList.toggle('text-indigo-800', selected);
            const check = button.querySelector('.branch-check');
            if (check) check.textContent = selected ? '✓' : '';
        });
        const count = selectedIds.size;
        summary.textContent = count ? `${count} ${count === 1 ? singularLabel : pluralLabel}` : selectLabel;
        summary.classList.toggle('text-slate-500', count === 0);
        summary.classList.toggle('text-slate-900', count > 0);
        clearButton.classList.toggle('hidden', count === 0);
        syncSelectedInputs();
    };

    const bindButtons = () => {
        options.querySelectorAll('.branch-option').forEach((button) => {
            button.addEventListener('click', () => {
                if (selectedIds.has(button.dataset.id)) selectedIds.delete(button.dataset.id);
                else selectedIds.add(button.dataset.id);
                syncButtons();
            });
        });
        syncButtons();
    };

    const renderBranches = (branches) => {
        options.replaceChildren();
        if (!branches.length) {
            const empty = document.createElement('p');
            empty.className = 'px-3 py-3 text-sm text-slate-500';
            empty.textContent = emptyText;
            options.appendChild(empty);
            syncButtons();
            return;
        }
        branches.slice(0, 5).forEach((branch) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'branch-option flex w-full items-center justify-between gap-3 rounded-md px-3 py-2 text-start text-sm hover:bg-indigo-50';
            button.dataset.id = branch.id;
            button.dataset.name = branch.name;
            const name = document.createElement('span');
            name.className = 'min-w-0 truncate font-medium';
            name.textContent = branch.name;
            const checkBox = document.createElement('span');
            checkBox.className = 'flex h-5 w-5 shrink-0 items-center justify-center rounded border border-slate-300 text-xs text-indigo-600';
            const check = document.createElement('span');
            check.className = 'branch-check';
            check.setAttribute('aria-hidden', 'true');
            checkBox.appendChild(check);
            button.append(name, checkBox);
            options.appendChild(button);
        });
        bindButtons();
    };

    const searchBranches = async () => {
        const term = searchInput.value.trim();
        if (!term) {
            renderBranches(initialBranches);
            return;
        }
        controller?.abort();
        controller = new AbortController();
        try {
            const url = new URL(searchUrl, window.location.origin);
            url.searchParams.set('q', term);
            const response = await fetch(url, { headers: { Accept: 'application/json' }, signal: controller.signal });
            if (!response.ok) throw new Error('Branch search failed');
            renderBranches(await response.json());
        } catch (error) {
            if (error.name !== 'AbortError') console.error(error);
        }
    };

    trigger.addEventListener('click', () => {
        if (results.classList.contains('hidden')) open();
        else close();
    });
    clearButton.addEventListener('click', () => {
        selectedIds.clear();
        searchInput.value = '';
        renderBranches(initialBranches);
        close();
    });
    document.addEventListener('click', (event) => {
        if (!branchPicker.contains(event.target)) close();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') close();
    });
    searchInput.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(searchBranches, 250);
    });
    bindButtons();
} else if (branchPicker) {
    const searchInput = branchPicker.querySelector('#branch-search');
    const results = branchPicker.querySelector('#branch-results');
    const selectedInputs = branchPicker.querySelector('#selected-branch-inputs');
    const searchUrl = branchPicker.dataset.searchUrl;
    const emptyText = branchPicker.dataset.emptyText;
    const selectedIds = new Set((branchPicker.dataset.selectedIds || '').split(',').filter(Boolean));
    let timer;
    let controller;

    const syncSelectedInputs = () => {
        selectedInputs.replaceChildren();
        selectedIds.forEach((id) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'branch_ids[]';
            input.value = id;
            selectedInputs.appendChild(input);
        });
    };

    const syncButtons = () => {
        results.querySelectorAll('.branch-option').forEach((button) => {
            const selected = selectedIds.has(button.dataset.id);
            button.dataset.selected = selected ? '1' : '0';
            button.setAttribute('aria-pressed', selected ? 'true' : 'false');
            button.classList.toggle('bg-indigo-100', selected);
            button.classList.toggle('text-indigo-800', selected);
            const check = button.querySelector('.branch-check');
            if (check) check.textContent = selected ? '✓' : '';
        });
        syncSelectedInputs();
    };

    const bindButtons = () => {
        results.querySelectorAll('.branch-option').forEach((button) => {
            button.addEventListener('click', () => {
                if (selectedIds.has(button.dataset.id)) selectedIds.delete(button.dataset.id);
                else selectedIds.add(button.dataset.id);
                syncButtons();
            });
        });
        syncButtons();
    };

    const renderBranches = (branches) => {
        results.replaceChildren();
        if (!branches.length) {
            const empty = document.createElement('p');
            empty.className = 'px-3 py-3 text-sm text-slate-500';
            empty.textContent = emptyText;
            results.appendChild(empty);
            return;
        }
        branches.slice(0, 5).forEach((branch) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'branch-option flex w-full items-center justify-between rounded-md px-3 py-2 text-start text-sm hover:bg-indigo-50';
            button.dataset.id = branch.id;
            button.dataset.name = branch.name;
            button.dataset.selected = selectedIds.has(String(branch.id)) ? '1' : '0';
            button.setAttribute('aria-pressed', selectedIds.has(String(branch.id)) ? 'true' : 'false');
            const name = document.createElement('span');
            name.className = 'font-medium';
            name.textContent = branch.name;
            const check = document.createElement('span');
            check.className = 'branch-check text-indigo-600';
            check.textContent = selectedIds.has(String(branch.id)) ? '✓' : '';
            button.append(name, check);
            results.appendChild(button);
        });
        bindButtons();
    };

    const searchBranches = async () => {
        const term = searchInput.value.trim();
        if (!term) {
            results.querySelectorAll('.branch-option').forEach((option) => option.classList.remove('hidden'));
            results.querySelectorAll('.branch-option:nth-child(n+6)').forEach((option) => option.classList.add('hidden'));
            bindButtons();
            return;
        }
        controller?.abort();
        controller = new AbortController();
        try {
            const url = new URL(searchUrl, window.location.origin);
            url.searchParams.set('q', term);
            const response = await fetch(url, { headers: { Accept: 'application/json' }, signal: controller.signal });
            if (!response.ok) throw new Error('Branch search failed');
            renderBranches(await response.json());
        } catch (error) {
            if (error.name !== 'AbortError') console.error(error);
        }
    };

    bindButtons();
    searchInput.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(searchBranches, 250);
    });
}


const dashboardChartData = document.querySelector('#dashboard-chart-data');

if (dashboardChartData) {
    try {
        const { charts } = JSON.parse(dashboardChartData.textContent || '{}');
        const chartInstances = [];
        const palette = ['#4f46e5', '#0891b2', '#16a34a', '#ea580c', '#dc2626', '#9333ea', '#ca8a04', '#0f766e'];
        const chartTextColor = () => document.documentElement.dataset.theme === 'dark' ? '#cbd5e1' : '#475569';
        const chartGridColor = () => document.documentElement.dataset.theme === 'dark' ? '#334155' : '#e2e8f0';
        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            plugins: {
                legend: { labels: { color: chartTextColor(), usePointStyle: true, boxWidth: 8 } },
                tooltip: { mode: 'index', intersect: false },
            },
        };

        const addChart = (key, type, options = {}) => {
            const canvas = document.querySelector(`#${key}-chart`);
            const rows = charts?.[key] || [];
            if (!canvas || !rows.length) return;
            const isDoughnut = type === 'doughnut';
            const chart = new Chart(canvas, {
                type,
                data: {
                    labels: rows.map((row) => row.name),
                    datasets: [{
                        data: rows.map((row) => row.total),
                        backgroundColor: rows.map((row, index) => row.color || palette[index % palette.length]),
                        borderColor: isDoughnut ? (document.documentElement.dataset.theme === 'dark' ? '#0f172a' : '#ffffff') : '#4f46e5',
                        borderWidth: isDoughnut ? 2 : 0,
                        borderRadius: isDoughnut ? 0 : 5,
                    }],
                },
                options: {
                    ...commonOptions,
                    ...options,
                    plugins: {
                        ...commonOptions.plugins,
                        legend: { ...commonOptions.plugins.legend, display: isDoughnut },
                        ...(options.plugins || {}),
                    },
                    scales: isDoughnut ? undefined : {
                        x: { beginAtZero: true, ticks: { color: chartTextColor(), precision: 0 }, grid: { color: chartGridColor() } },
                        y: { ticks: { color: chartTextColor() }, grid: { display: false } },
                    },
                },
            });
            chartInstances.push(chart);
        };

        const trendCanvas = document.querySelector('#trend-chart');
        if (trendCanvas && charts?.trend) {
            const trend = charts.trend;
            chartInstances.push(new Chart(trendCanvas, {
                type: 'line',
                data: {
                    labels: trend.labels,
                    datasets: [{
                        label: document.documentElement.lang === 'ar' ? 'الشكاوى' : 'Complaints',
                        data: trend.values,
                        borderColor: '#4f46e5',
                        backgroundColor: 'rgba(79, 70, 229, .14)',
                        fill: true,
                        tension: .35,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                    }],
                },
                options: {
                    ...commonOptions,
                    plugins: { ...commonOptions.plugins, legend: { display: false } },
                    scales: {
                        x: { ticks: { color: chartTextColor(), maxRotation: 0, autoSkip: true }, grid: { display: false } },
                        y: { beginAtZero: true, ticks: { color: chartTextColor(), precision: 0 }, grid: { color: chartGridColor() } },
                    },
                },
            }));
        }

        addChart('branches', 'bar', { indexAxis: 'y' });
        addChart('categories', 'bar', { indexAxis: 'y' });
        addChart('types', 'bar', { indexAxis: 'y' });
        addChart('services', 'bar', { indexAxis: 'y' });
        addChart('sources', 'doughnut', { cutout: '62%' });
        addChart('statuses', 'doughnut', { cutout: '62%' });
        addChart('priorities', 'doughnut', { cutout: '62%' });

        const refreshChartTheme = () => {
            chartInstances.forEach((chart) => {
                if (chart.options.scales) {
                    Object.values(chart.options.scales).forEach((scale) => {
                        if (scale.ticks) scale.ticks.color = chartTextColor();
                        if (scale.grid) scale.grid.color = chartGridColor();
                    });
                }
                if (chart.options.plugins?.legend?.labels) chart.options.plugins.legend.labels.color = chartTextColor();
                chart.update('none');
            });
        };
        themeToggles.forEach((t) => t.addEventListener('click', () => window.setTimeout(refreshChartTheme, 0)));
    } catch (error) {
        console.warn('Dashboard chart initialization failed.', error);
    }
}

const visitReportDashboard = document.querySelector('#visit-report-chart-data');

if (visitReportDashboard) {
    try {
        const { charts } = JSON.parse(visitReportDashboard.textContent || '{}');
        const chartInstances = [];
        const palette = ['#4f46e5', '#0891b2', '#16a34a', '#ea580c', '#dc2626', '#9333ea', '#ca8a04', '#0f766e'];
        const chartTextColor = () => document.documentElement.dataset.theme === 'dark' ? '#cbd5e1' : '#475569';
        const chartGridColor = () => document.documentElement.dataset.theme === 'dark' ? '#334155' : '#e2e8f0';
        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            plugins: {
                legend: { labels: { color: chartTextColor(), usePointStyle: true, boxWidth: 8 } },
                tooltip: { mode: 'index', intersect: false },
            },
        };

        const addChart = (key, type, options = {}) => {
            const canvas = document.querySelector(`#report-${key}-chart`);
            const rows = charts?.[key] || [];
            if (!canvas || !rows.length) return;
            const isDoughnut = type === 'doughnut';
            const chart = new Chart(canvas, {
                type,
                data: {
                    labels: rows.map((row) => row.name),
                    datasets: [{
                        data: rows.map((row) => row.total),
                        backgroundColor: rows.map((row, index) => row.color || palette[index % palette.length]),
                        borderColor: isDoughnut ? (document.documentElement.dataset.theme === 'dark' ? '#0f172a' : '#ffffff') : '#4f46e5',
                        borderWidth: isDoughnut ? 2 : 0,
                        borderRadius: isDoughnut ? 0 : 5,
                    }],
                },
                options: {
                    ...commonOptions,
                    ...options,
                    plugins: {
                        ...commonOptions.plugins,
                        legend: { ...commonOptions.plugins.legend, display: isDoughnut },
                        ...(options.plugins || {}),
                    },
                    scales: isDoughnut ? undefined : {
                        x: { beginAtZero: true, ticks: { color: chartTextColor(), precision: 0 }, grid: { color: chartGridColor() } },
                        y: { ticks: { color: chartTextColor() }, grid: { display: false } },
                    },
                },
            });
            chartInstances.push(chart);
        };

        const trendCanvas = document.querySelector('#report-trend-chart');
        if (trendCanvas && charts?.trend?.labels?.length) {
            chartInstances.push(new Chart(trendCanvas, {
                type: 'line',
                data: {
                    labels: charts.trend.labels,
                    datasets: [{
                        label: document.documentElement.lang === 'ar' ? 'متوسط النتيجة' : 'Average score',
                        data: charts.trend.values,
                        borderColor: '#4f46e5',
                        backgroundColor: 'rgba(79, 70, 229, .14)',
                        fill: true,
                        tension: .35,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                    }],
                },
                options: {
                    ...commonOptions,
                    plugins: { ...commonOptions.plugins, legend: { display: false } },
                    scales: {
                        x: { ticks: { color: chartTextColor(), maxRotation: 0, autoSkip: true }, grid: { display: false } },
                        y: { beginAtZero: true, max: 100, ticks: { color: chartTextColor(), callback: (v) => v + '%' }, grid: { color: chartGridColor() } },
                    },
                },
            }));
        }

        addChart('branch', 'bar', { indexAxis: 'y' });
        addChart('severity', 'doughnut', { cutout: '62%' });
        addChart('section', 'bar', { indexAxis: 'y' });
        addChart('rootCause', 'doughnut', { cutout: '62%' });

        const refreshReportChartTheme = () => {
            chartInstances.forEach((chart) => {
                if (chart.options.scales) {
                    Object.values(chart.options.scales).forEach((scale) => {
                        if (scale.ticks) scale.ticks.color = chartTextColor();
                        if (scale.grid) scale.grid.color = chartGridColor();
                    });
                }
                if (chart.options.plugins?.legend?.labels) chart.options.plugins.legend.labels.color = chartTextColor();
                chart.update('none');
            });
        };
        themeToggles.forEach((t) => t.addEventListener('click', () => window.setTimeout(refreshReportChartTheme, 0)));
    } catch (error) {
        console.warn('Visit report chart initialization failed.', error);
    }
}

if ('serviceWorker' in navigator) {
    window.addEventListener('load', async () => {
        const manifestLink = document.querySelector('link[rel="manifest"]');
        if (!manifestLink) return;

        try {
            const manifestUrl = new URL(manifestLink.href);
            const serviceWorkerUrl = new URL('service-worker.js', manifestUrl);
            const scope = new URL('./', manifestUrl).pathname;
            const registration = await navigator.serviceWorker.register(serviceWorkerUrl.pathname, {
                scope,
                updateViaCache: 'none',
            });
            await registration.update();
        } catch (error) {
            console.warn('PWA service worker registration failed.', error);
        }
    });
}

(() => {
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true
        || window.matchMedia('(display-mode: window-controls-overlay)').matches;
    if (isStandalone) return;

    const desktopBtn = document.querySelector('[data-install-button]');
    const mobileBtn = document.querySelector('[data-install-button-mobile]');
    const modal = document.querySelector('[data-install-modal]');
    const androidPara = modal && modal.querySelector('[data-install-android]');
    const iosPara = modal && modal.querySelector('[data-install-ios]');
    const overlay = modal && modal.querySelector('[data-install-overlay]');
    const closeBtn = modal && modal.querySelector('[data-install-close]');
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;

    let deferredPrompt = null;

    const reveal = (btn) => {
        if (!btn) return;
        btn.classList.remove('hidden');
        btn.classList.add('flex');
    };

    const openModal = (android) => {
        if (!modal) return;
        androidPara?.classList.toggle('hidden', !android);
        iosPara?.classList.toggle('hidden', android);
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('aria-hidden', 'false');
        closeBtn?.focus?.();
    };

    const closeModal = () => {
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('aria-hidden', 'true');
    };

    if (!isIOS) {
        window.addEventListener('beforeinstallprompt', (event) => {
            event.preventDefault();
            deferredPrompt = event;
            reveal(desktopBtn);
            reveal(mobileBtn);
        });
        if (mobileBtn) reveal(mobileBtn);
    } else if (mobileBtn) {
        reveal(mobileBtn);
    }

    const triggerInstall = (event) => {
        event.preventDefault();
        if (isIOS) {
            openModal(false);
            return;
        }
        if (!deferredPrompt) {
            openModal(true);
            return;
        }
        deferredPrompt.prompt();
        deferredPrompt.userChoice.then((choiceResult) => {
            if (choiceResult.outcome === 'dismissed') openModal(true);
            deferredPrompt = null;
            desktopBtn?.classList.add('hidden');
            mobileBtn?.classList.add('hidden');
        });
    };

    desktopBtn?.addEventListener('click', triggerInstall);
    mobileBtn?.addEventListener('click', triggerInstall);
    overlay?.addEventListener('click', closeModal);
    closeBtn?.addEventListener('click', closeModal);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });
    window.addEventListener('appinstalled', () => {
        deferredPrompt = null;
        desktopBtn?.classList.add('hidden');
        mobileBtn?.classList.add('hidden');
    });
})();
