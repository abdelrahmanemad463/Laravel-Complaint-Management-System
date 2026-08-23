import './bootstrap';

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

