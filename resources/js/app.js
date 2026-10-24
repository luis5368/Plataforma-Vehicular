import './bootstrap';

import 'bootstrap';
import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();

// Presentational helpers; no routes, request payloads or business state are changed.
document.addEventListener('click', event => {
    const toggle = event.target.closest('[data-password-toggle]');
    if (!toggle) return;
    const input = document.getElementById(toggle.getAttribute('aria-controls'));
    if (!input) return;
    const visible = input.type === 'password';
    input.type = visible ? 'text' : 'password';
    toggle.setAttribute('aria-pressed', String(visible));
    toggle.setAttribute('aria-label', visible ? 'Ocultar contraseña' : 'Mostrar contraseña');
});
document.addEventListener('submit', event => {
    if (event.defaultPrevented || !(event.target instanceof HTMLFormElement)) return;
    const form = event.target;
    if (form.dataset.busy === 'true') { event.preventDefault(); return; }
    form.dataset.busy = 'true';
    form.setAttribute('aria-busy', 'true');
    const button = event.submitter;
    if (button) button.classList.add('is-loading');
});
window.addEventListener('pageshow', () => {
    document.querySelectorAll('form[data-busy]').forEach(form => { delete form.dataset.busy; form.removeAttribute('aria-busy'); });
    document.querySelectorAll('.is-loading').forEach(button => button.classList.remove('is-loading'));
});

import './regional-map';

import './report-photos';
import './reported-theft-map';
