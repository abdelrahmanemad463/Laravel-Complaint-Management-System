import './bootstrap';

const themeToggle = document.querySelector('[data-theme-toggle]');
const themeColorMeta = document.querySelector('[data-theme-color]');

const applyTheme = (theme) => {
    const normalizedTheme = theme === 'dark' ? 'dark' : 'light';
    const isDark = normalizedTheme === 'dark';
    document.documentElement.dataset.theme = normalizedTheme;
    document.documentElement.style.colorScheme = normalizedTheme;
    if (themeColorMeta) themeColorMeta.content = isDark ? '#0f172a' : '#4f46e5';
    if (!themeToggle) return;
    themeToggle.setAttribute('aria-pressed', isDark ? 'true' : 'false');
    themeToggle.setAttribute('aria-label', themeToggle.dataset.themeSwitcher || themeToggle.getAttribute('aria-label') || 'Switch theme');
    const icon = themeToggle.querySelector('[data-theme-icon]');
    const label = themeToggle.querySelector('[data-theme-label]');
    if (icon) icon.textContent = isDark ? '☀' : '☾';
    if (label) label.textContent = isDark ? themeToggle.dataset.lightLabel : themeToggle.dataset.darkLabel;
};

applyTheme(document.documentElement.dataset.theme || 'light');

themeToggle?.addEventListener('click', () => {
    const nextTheme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
    applyTheme(nextTheme);
    window.localStorage.setItem('complaint-theme', nextTheme);
});

const customerPicker = document.querySelector('[data-customer-picker]');

if (customerPicker) {
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

if (branchPicker) {
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
