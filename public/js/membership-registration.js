(function () {
    'use strict';
    const form = document.getElementById('membership-registration');
    if (!form) return;
    const months = form.querySelector('[name="months"]');
    const total = document.getElementById('membership-total');
    const fee = Number(form.dataset.monthlyFee);
    const update = () => {
        const count = Number(months.value);
        total.textContent = Number.isInteger(count) && count >= 1 && count <= 12 && Number.isFinite(fee)
            ? new Intl.NumberFormat('vi-VN').format(fee * count) + ' đ' : '—';
    };
    months.addEventListener('change', update);
    let submitting = false;
    form.addEventListener('submit', event => {
        if (submitting) { event.preventDefault(); return; }
        if (!form.checkValidity()) { event.preventDefault(); form.reportValidity(); return; }
        submitting = true;
        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        button.textContent = 'Đang gửi đăng ký…';
    });
    window.addEventListener('pageshow', () => {
        submitting = false;
        const button = form.querySelector('button[type="submit"]');
        button.disabled = false;
        button.textContent = form.dataset.submitLabel;
    });
    form.dataset.submitLabel = form.querySelector('button[type="submit"]').textContent;
    update();
})();
