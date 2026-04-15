document.addEventListener('DOMContentLoaded', () => {
    const cartAddForm = document.querySelector('[data-cart-add-form]');
    const cartActionButton = document.querySelector('[data-cart-action-button]');

    if (!cartAddForm || !cartActionButton) {
        return;
    }

    let hasBeenAdded = false;

    const switchToGoCartState = () => {
        hasBeenAdded = true;
        cartActionButton.textContent = '🛒 Savatga o\'tish';
        cartActionButton.style.background = '#111827';
        cartActionButton.style.border = '2px solid #111827';
        cartActionButton.style.color = '#ffffff';
        cartActionButton.style.boxShadow = '0 0 0 3px rgba(17, 24, 39, 0.15)';
        cartActionButton.style.transform = 'translateY(-1px)';
    };

    cartAddForm.addEventListener('submit', async (event) => {
        if (hasBeenAdded) {
            event.preventDefault();
            const cartUrl = cartAddForm.dataset.cartUrl || '/cart';
            window.location.href = cartUrl;
            return;
        }

        event.preventDefault();

        const submitUrl = cartAddForm.getAttribute('action');
        const formData = new FormData(cartAddForm);

        try {
            const response = await fetch(submitUrl, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
            });

            const result = await response.json();
            if (!response.ok || !result.success) {
                const message = result.message || 'Savatga qo\'shishda xatolik yuz berdi.';
                window.alert(message);
                return;
            }

            switchToGoCartState();
        } catch (error) {
            window.alert('Savatga qo\'shishda xatolik yuz berdi. Qaytadan urinib ko\'ring.');
        }
    });
});
