document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    const message = form.dataset.confirm;
    if (message && !window.confirm(message)) {
        event.preventDefault();
    }
});

document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-confirm]');
    if (trigger && !(trigger instanceof HTMLFormElement)) {
        const message = trigger.dataset.confirm;
        if (message && !window.confirm(message)) {
            event.preventDefault();
            return;
        }
    }

    const openTrigger = event.target.closest('[data-dialog-open]');
    if (openTrigger) {
        const dialogId = openTrigger.dataset.dialogOpen;
        const dialog = dialogId ? document.getElementById(dialogId) : null;
        if (dialog && typeof dialog.showModal === 'function') {
            dialog.showModal();
        }
        return;
    }

    const closeTrigger = event.target.closest('[data-dialog-close]');
    if (closeTrigger) {
        const dialogId = closeTrigger.dataset.dialogClose;
        const dialog = dialogId ? document.getElementById(dialogId) : closeTrigger.closest('dialog');
        if (dialog && typeof dialog.close === 'function') {
            dialog.close();
        }
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Enter' && event.key !== ' ') {
        return;
    }

    const openTrigger = event.target.closest('[data-dialog-open]');
    if (!openTrigger) {
        return;
    }

    event.preventDefault();
    const dialogId = openTrigger.dataset.dialogOpen;
    const dialog = dialogId ? document.getElementById(dialogId) : null;
    if (dialog && typeof dialog.showModal === 'function') {
        dialog.showModal();
    }
});

document.querySelectorAll('dialog[data-dialog-backdrop-close]').forEach((dialog) => {
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog && typeof dialog.close === 'function') {
            dialog.close();
        }
    });
});
