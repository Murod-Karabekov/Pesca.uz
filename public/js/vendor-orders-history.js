document.querySelectorAll('.vendor-history-row').forEach((row) => {
    row.addEventListener('click', () => {
        const historyId = row.dataset.historyId;
        const detailsRow = document.querySelector('[data-history-details-id="' + historyId + '"]');
        if (detailsRow) {
            detailsRow.classList.toggle('hidden');
        }
    });
});
