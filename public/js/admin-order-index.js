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

document.querySelectorAll('.order-row').forEach((row) => {
    row.addEventListener('click', (event) => {
        if (event.target.closest('.order-actions')) {
            return;
        }

        const orderId = row.dataset.orderId;
        const detailsRow = document.querySelector('[data-order-details-id="' + orderId + '"]');

        if (!detailsRow) {
            return;
        }

        detailsRow.classList.toggle('hidden');
    });
});

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
            button.classList.add('border-green-400', 'text-green-700', 'bg-green-50');
            setTimeout(() => {
                button.classList.remove('border-green-400', 'text-green-700', 'bg-green-50');
            }, 1200);
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
