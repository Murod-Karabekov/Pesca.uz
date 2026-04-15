const copyToast = document.getElementById('copy-toast');

function showCopyToast() {
    if (!copyToast) {
        return;
    }

    copyToast.classList.remove('hidden');
    setTimeout(() => {
        copyToast.classList.add('hidden');
    }, 1200);
}

document.querySelectorAll('.copy-order-btn').forEach((button) => {
    button.addEventListener('click', async () => {
        const copyTarget = button.dataset.copyTarget || '';
        const textarea = copyTarget ? document.getElementById(copyTarget) : null;
        const text = textarea ? textarea.value.trim() : '';

        if (!text) {
            return;
        }

        try {
            await navigator.clipboard.writeText(text);
            showCopyToast();
        } catch (e) {
            const temp = document.createElement('textarea');
            temp.value = text;
            document.body.appendChild(temp);
            temp.select();
            document.execCommand('copy');
            document.body.removeChild(temp);
            showCopyToast();
        }
    });
});
