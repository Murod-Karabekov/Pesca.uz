document.getElementById('copy-order')?.addEventListener('click', async function () {
    const textarea = document.getElementById('order-copy');
    if (!textarea) {
        return;
    }

    try {
        await navigator.clipboard.writeText(textarea.value);
        this.textContent = 'Nusxalandi!';
        setTimeout(() => {
            this.textContent = 'Matnni nusxalash';
        }, 1500);
    } catch (e) {
        textarea.select();
        document.execCommand('copy');
    }
});
