document.addEventListener('DOMContentLoaded', () => {
    const cartSelectionInputs = Array.from(document.querySelectorAll('[data-cart-select]'));
    const selectedTotalElement = document.querySelector('[data-selected-total]');
    const orderSubmitButton = document.getElementById('order-submit-btn');
    const toggleAllButton = document.getElementById('toggle-select-all-btn');
    const cartLineItems = Array.from(document.querySelectorAll('[data-cart-line-item]'));

    if (!cartSelectionInputs.length || !selectedTotalElement || !orderSubmitButton) {
        return;
    }

    const formatSum = (amount) => {
        return new Intl.NumberFormat('uz-UZ').format(Math.round(amount)) + ' so\'m';
    };

    const updateSelectedSummary = () => {
        let total = 0;
        let selectedCount = 0;

        cartSelectionInputs.forEach((input) => {
            const itemCard = input.closest('[data-cart-line-item]');
            const lineTotal = Number(itemCard?.dataset.itemTotal || 0);

            if (input.checked) {
                total += lineTotal;
                selectedCount += 1;
            }
        });

        selectedTotalElement.textContent = formatSum(total);
        orderSubmitButton.disabled = selectedCount === 0;
        orderSubmitButton.classList.toggle('opacity-50', selectedCount === 0);
        orderSubmitButton.classList.toggle('cursor-not-allowed', selectedCount === 0);

        if (toggleAllButton) {
            toggleAllButton.textContent = selectedCount === cartSelectionInputs.length
                ? 'Barchasini bekor qilish'
                : 'Barchasini belgilash';
        }
    };

    cartSelectionInputs.forEach((input) => {
        input.addEventListener('change', updateSelectedSummary);
    });

    const updateItemQuantity = async (itemCard, newQuantity) => {
        const updateUrl = itemCard.dataset.updateUrl || '';
        const token = itemCard.dataset.updateToken || '';

        if (!updateUrl || !token) {
            return;
        }

        const payload = new URLSearchParams();
        payload.append('_token', token);
        payload.append('quantity', String(newQuantity));

        const response = await fetch(updateUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: payload.toString(),
        });

        const result = await response.json();
        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Miqdor yangilanmadi');
        }

        const quantityEl = itemCard.querySelector('[data-quantity-value]');
        const lineTotalEl = itemCard.querySelector('[data-line-total-value]');

        if (quantityEl) {
            quantityEl.textContent = String(result.quantity);
        }

        itemCard.dataset.itemTotal = String(result.itemTotal || 0);

        if (lineTotalEl) {
            lineTotalEl.textContent = formatSum(Number(result.itemTotal || 0));
        }

        updateSelectedSummary();
    };

    cartLineItems.forEach((itemCard) => {
        const quantityEl = itemCard.querySelector('[data-quantity-value]');
        const quantityButtons = Array.from(itemCard.querySelectorAll('[data-qty-change]'));

        quantityButtons.forEach((button) => {
            button.addEventListener('click', async () => {
                const delta = Number(button.dataset.qtyChange || 0);
                const currentQty = Number(quantityEl?.textContent || 1);
                const nextQty = Math.max(1, currentQty + delta);

                if (nextQty === currentQty) {
                    return;
                }

                quantityButtons.forEach((btn) => {
                    btn.disabled = true;
                });

                try {
                    await updateItemQuantity(itemCard, nextQty);
                } catch (error) {
                    window.alert('Miqdorni yangilashda xatolik yuz berdi.');
                } finally {
                    quantityButtons.forEach((btn) => {
                        btn.disabled = false;
                    });
                }
            });
        });
    });

    toggleAllButton?.addEventListener('click', () => {
        const allSelected = cartSelectionInputs.every((input) => input.checked);
        cartSelectionInputs.forEach((input) => {
            input.checked = !allSelected;
        });

        updateSelectedSummary();
    });

    updateSelectedSummary();
});
