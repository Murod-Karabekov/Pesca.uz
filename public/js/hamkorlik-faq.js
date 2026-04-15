document.querySelectorAll('#faq-accordion .faq-toggle').forEach((btn) => {
    btn.addEventListener('click', () => {
        const item = btn.closest('.faq-item');
        if (!item) {
            return;
        }

        const answer = item.querySelector('.faq-answer');
        const icon = item.querySelector('.faq-icon');
        const isOpen = answer && !answer.classList.contains('hidden');

        document.querySelectorAll('#faq-accordion .faq-item').forEach((el) => {
            el.querySelector('.faq-answer')?.classList.add('hidden');
            el.querySelector('.faq-icon')?.classList.remove('rotate-180');
        });

        if (!isOpen) {
            answer?.classList.remove('hidden');
            icon?.classList.add('rotate-180');
        }
    });
});
