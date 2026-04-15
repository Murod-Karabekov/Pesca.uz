document.addEventListener('DOMContentLoaded', () => {
    const panels = Array.from(document.querySelectorAll('[data-mobile-sidebar]'));

    panels.forEach((panel) => {
        const id = panel.getAttribute('id');
        if (!id) {
            return;
        }

        const openButtons = Array.from(document.querySelectorAll('[data-mobile-sidebar-open="' + id + '"]'));
        const closeButtons = Array.from(document.querySelectorAll('[data-mobile-sidebar-close="' + id + '"]'));
        const backdrop = panel.querySelector('[data-mobile-sidebar-backdrop]');
        const drawer = panel.querySelector('[data-mobile-sidebar-drawer]');

        const openPanel = () => {
            panel.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
            requestAnimationFrame(() => {
                backdrop?.classList.remove('opacity-0');
                backdrop?.classList.add('opacity-100');
                drawer?.classList.remove('-translate-x-full');
            });
        };

        const closePanel = () => {
            backdrop?.classList.add('opacity-0');
            backdrop?.classList.remove('opacity-100');
            drawer?.classList.add('-translate-x-full');
            setTimeout(() => {
                panel.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }, 200);
        };

        openButtons.forEach((btn) => btn.addEventListener('click', openPanel));
        closeButtons.forEach((btn) => btn.addEventListener('click', closePanel));
        backdrop?.addEventListener('click', closePanel);

        panel.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', closePanel);
        });
    });
});
