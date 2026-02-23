import './styles/app.css';

// ─── Mobile Nav Toggle ──────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.getElementById('mobile-menu-toggle');
  const menu = document.getElementById('mobile-menu');
  if (toggle && menu) {
    toggle.addEventListener('click', () => {
      menu.classList.toggle('hidden');
    });
  }

  // ─── Flash Message Auto-dismiss ─────────────────
  document.querySelectorAll('[data-flash]').forEach(el => {
    setTimeout(() => {
      el.style.transition = 'opacity 0.5s ease-out';
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 500);
    }, 4000);
  });
});
