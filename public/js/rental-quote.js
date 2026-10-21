(() => {
    const form = document.getElementById('rental-form');
    if (!form) return;
    const input = document.getElementById('rental-days');
    const button = document.getElementById('reserve-button');
    const quote = document.getElementById('rental-quote');
    const error = document.getElementById('quote-error');
    const currency = new Intl.NumberFormat('vi-VN');
    let controller, timer, version = 0;
    let confirmedDays = Number(input.value);
    const money = (id, value) => { document.getElementById(id).textContent = currency.format(value) + ' đ'; };
    const fail = message => { error.textContent = message; error.hidden = false; button.disabled = true; };
    async function update() {
        clearTimeout(timer);
        const current = ++version;
        if (controller) controller.abort();
        error.hidden = true;
        button.disabled = true;
        confirmedDays = null;
        const days = Number(input.value);
        if (!input.validity.valid || !Number.isInteger(days)) {
            quote.hidden = true;
            fail(`Vui lòng nhập số ngày nguyên từ ${input.min} đến ${input.max}.`);
            return;
        }
        // Never display a previous day's figures as the current estimate.
        quote.hidden = true;
        quote.setAttribute('aria-busy', 'true');
        timer = setTimeout(async () => {
            controller = new AbortController();
            try {
                const url = new URL(form.dataset.quoteUrl, location.href);
                url.searchParams.set('days', days);
                const response = await fetch(url, { signal: controller.signal, headers: { Accept: 'application/json' }, cache: 'no-store' });
                const data = await response.json();
                if (current !== version) return;
                if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'Không tính được chi phí.');
                document.getElementById('quote-days').textContent = data.days;
                money('quote-fee', data.rental_fee);
                money('quote-discount', data.discount_amount);
                money('quote-deposit', data.deposit_amount);
                money('quote-total', data.total_amount);
                document.getElementById('quote-due').textContent = data.due_date.split('-').reverse().join('/');
                quote.hidden = false;
                confirmedDays = data.days;
                button.disabled = button.dataset.unavailable === '1';
            } catch (e) {
                if (e.name !== 'AbortError' && current === version) fail('Chưa cập nhật được chi phí. Vui lòng kiểm tra kết nối rồi chọn lại số ngày.');
            } finally {
                if (current === version) quote.setAttribute('aria-busy', 'false');
            }
        }, 150);
    }
    input.addEventListener('input', update);
    input.addEventListener('change', update);
    form.addEventListener('submit', e => {
        if (e.submitter && e.submitter.getAttribute('formmethod') === 'GET') return;
        if (confirmedDays !== Number(input.value)) {
            e.preventDefault();
            fail('Vui lòng chờ bảng chi phí cập nhật trước khi đặt sách.');
        }
    });
})();
