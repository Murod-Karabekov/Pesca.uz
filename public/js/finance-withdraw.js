document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('card-number-input');
    if (!input) return;

    input.addEventListener('input', function(e) {
        let value = this.value.replace(/\D/g, '');
        if (value.length > 16) value = value.slice(0, 16);
        this.value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
    });

    input.addEventListener('keydown', function(e) {
        if (e.key === 'Backspace' && this.value.endsWith(' ')) {
            e.preventDefault();
            this.value = this.value.slice(0, -2);
        }
    });
});
