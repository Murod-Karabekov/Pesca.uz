document.addEventListener('DOMContentLoaded', function() {
    const banners = Array.from(document.querySelectorAll('dialog[data-banner-modal]')).map((dialog) => {
        const id = dialog.id;
        return {
            dialog,
            countdownEl: document.getElementById('banner_countdown_' + id.replace('banner_modal_', '')),
            ringEl: document.getElementById('banner_ring_' + id.replace('banner_modal_', '')),
            displaySeconds: Number(dialog.dataset.displaySeconds || 0),
        };
    });

    const appearanceDelaySeconds = 3;

    banners.forEach(function(item) {
        const dialog = item.dialog;
        const countdownEl = item.countdownEl;
        const ringEl = item.ringEl;

        if (!dialog || !countdownEl || !ringEl) {
            return;
        }

        const totalDuration = Math.max(1, Number(item.displaySeconds) || 3);
        let remaining = totalDuration;

        const update = function() {
            countdownEl.textContent = String(remaining);
            countdownEl.classList.remove('banner-countdown');
            void countdownEl.offsetWidth;
            countdownEl.classList.add('banner-countdown');

            const progress = totalDuration > 0 ? ((totalDuration - remaining) / totalDuration) * 100 : 100;
            ringEl.style.setProperty('--progress', String(Math.min(100, Math.max(0, progress))));
        };

        update();

        setTimeout(function() {
            if (typeof dialog.showModal !== 'function') {
                return;
            }

            dialog.showModal();
            remaining = totalDuration;
            update();

            const interval = setInterval(function() {
                remaining -= 1;
                update();

                if (remaining <= 0) {
                    clearInterval(interval);
                    dialog.close();
                }
            }, 1000);
        }, appearanceDelaySeconds * 1000);

        dialog.addEventListener('close', function() {
            remaining = totalDuration;
            update();
        });
    });
});
