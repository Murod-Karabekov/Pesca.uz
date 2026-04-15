let pendingCancelForm = null;
const cancelModal = document.getElementById('cancel-modal');
const cancelModalYes = document.getElementById('cancel-modal-yes');
const cancelModalNo = document.getElementById('cancel-modal-no');

const closeCancelModal = () => {
    if (!cancelModal) {
        return;
    }

    cancelModal.classList.add('hidden');
    pendingCancelForm = null;
};

document.querySelectorAll('.vendor-cancel-form').forEach((form) => {
    form.dataset.modalConfirmed = '0';
    form.addEventListener('submit', (event) => {
        if (form.dataset.modalConfirmed === '1') {
            form.dataset.modalConfirmed = '0';
            return;
        }

        event.preventDefault();
        pendingCancelForm = form;
        cancelModal?.classList.remove('hidden');
    });
});

cancelModalYes?.addEventListener('click', () => {
    if (!pendingCancelForm) {
        closeCancelModal();
        return;
    }

    const form = pendingCancelForm;
    pendingCancelForm = null;
    cancelModal?.classList.add('hidden');

    form.dataset.modalConfirmed = '1';
    if (typeof form.requestSubmit === 'function') {
        form.requestSubmit();
    } else {
        form.submit();
    }
});

cancelModalNo?.addEventListener('click', closeCancelModal);

document.querySelectorAll('[data-cancel-modal-close]').forEach((el) => {
    el.addEventListener('click', closeCancelModal);
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && cancelModal && !cancelModal.classList.contains('hidden')) {
        closeCancelModal();
    }
});

document.querySelectorAll('.vendor-order-row').forEach((row) => {
    row.addEventListener('click', (event) => {
        if (event.target.closest('.order-actions')) {
            return;
        }

        const orderId = row.dataset.orderId;
        const detailsRow = document.querySelector('[data-order-details-id="' + orderId + '"]');
        if (detailsRow) {
            detailsRow.classList.toggle('hidden');
        }
    });
});
