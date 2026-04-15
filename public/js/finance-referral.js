const financeCopyBtn = document.getElementById('finance-referral-copy-btn');

financeCopyBtn?.addEventListener('click', () => {
    const input = document.getElementById('referral-link');
    if (!input) {
        return;
    }

    input.select();
    navigator.clipboard.writeText(input.value).then(() => {
        financeCopyBtn.textContent = '✅ Nusxalandi!';
        setTimeout(() => {
            financeCopyBtn.textContent = '📋 Nusxalash';
        }, 2000);
    });
});
