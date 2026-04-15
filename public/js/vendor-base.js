document.querySelectorAll('[data-flash]').forEach(el => {
    setTimeout(() => el.remove(), 4000);
});
