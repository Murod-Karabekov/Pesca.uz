const profileCopyBtn = document.getElementById('copy-btn');

profileCopyBtn?.addEventListener('click', () => {
    const input = document.getElementById('referral-link');
    if (!input) {
        return;
    }

    navigator.clipboard.writeText(input.value).then(() => {
        profileCopyBtn.textContent = '✅';
        setTimeout(() => {
            profileCopyBtn.textContent = '📋';
        }, 2000);
    });
});
