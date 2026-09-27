// Confirm before any destructive action
document.addEventListener('click', function (e) {
  const el = e.target.closest('[data-confirm]');
  if (el && !window.confirm(el.getAttribute('data-confirm'))) {
    e.preventDefault();
  }
});

// Auto-dismiss flash alerts
setTimeout(() => {
  document.querySelectorAll('[data-flash]').forEach(el => {
    el.style.transition = 'opacity .4s';
    el.style.opacity = '0';
    setTimeout(() => el.remove(), 400);
  });
}, 4000);
