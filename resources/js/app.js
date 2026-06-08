import { createApp } from 'vue';
import DashboardStats from './Components/DashboardStats.vue';

const mount = document.getElementById('app');

if (mount?.dataset.page === 'dashboard') {
    createApp(DashboardStats, JSON.parse(mount.dataset.props || '{}')).mount(mount);
}

document.querySelectorAll('[data-bulk-team-form]').forEach((form) => {
    const count = form.querySelector('[data-selected-count]');
    const checkboxes = [...form.querySelectorAll('input[type="checkbox"][name="team_ids[]"]')];

    const updateCount = () => {
        if (count) {
            count.textContent = checkboxes.filter((checkbox) => checkbox.checked).length;
        }
    };

    checkboxes.forEach((checkbox) => checkbox.addEventListener('change', updateCount));
    updateCount();
});

document.querySelectorAll('[data-copy-button]').forEach((button) => {
    button.addEventListener('click', async () => {
        const value = button.dataset.copyValue || '';
        const text = button.querySelector('[data-copy-text]');
        const originalLabel = button.dataset.copyLabel || 'Copy link';
        const copiedLabel = button.dataset.copyCopiedLabel || 'Copied';

        const copyWithFallback = async () => {
            if (navigator.clipboard?.writeText) {
                await navigator.clipboard.writeText(value);

                return;
            }

            const textarea = document.createElement('textarea');
            textarea.value = value;
            textarea.setAttribute('readonly', 'readonly');
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            textarea.remove();
        };

        try {
            await copyWithFallback();

            if (text) {
                text.textContent = copiedLabel;
            }

            button.classList.add('border-green-200', 'bg-green-50', 'text-green-800');

            setTimeout(() => {
                if (text) {
                    text.textContent = originalLabel;
                }

                button.classList.remove('border-green-200', 'bg-green-50', 'text-green-800');
            }, 1800);
        } catch {
            if (text) {
                text.textContent = 'Copy failed';
            }
        }
    });
});

document.querySelectorAll('[data-manage-container]').forEach((container) => {
    const toggle = container.querySelector('[data-manage-toggle]');
    const panel = container.querySelector('[data-manage-panel]');
    const openLabel = toggle?.querySelector('[data-manage-open-label]');
    const closeLabel = toggle?.querySelector('[data-manage-close-label]');

    toggle?.addEventListener('click', () => {
        const isOpening = panel?.classList.contains('hidden');

        panel?.classList.toggle('hidden', !isOpening);
        openLabel?.classList.toggle('hidden', isOpening);
        closeLabel?.classList.toggle('hidden', !isOpening);
        toggle.classList.toggle('border-red-200', isOpening);
        toggle.classList.toggle('text-brand-danger', isOpening);
        toggle.classList.toggle('hover:bg-red-50', isOpening);
        toggle.setAttribute('aria-expanded', String(isOpening));
    });
});

document.querySelectorAll('[data-scroll-to]').forEach((button) => {
    button.addEventListener('click', () => {
        document.querySelector(button.dataset.scrollTo || '')?.scrollIntoView({
            behavior: 'smooth',
            block: 'start',
        });
    });
});

const confirmDialog = document.getElementById('confirm-dialog');
const confirmTitle = confirmDialog?.querySelector('[data-confirm-title]');
const confirmMessage = confirmDialog?.querySelector('[data-confirm-message]');
const confirmButton = confirmDialog?.querySelector('[data-confirm-submit]');
let pendingForm = null;

document.querySelectorAll('[data-confirm-form]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (form.dataset.confirmed === 'true' || !confirmDialog) {
            delete form.dataset.confirmed;

            return;
        }

        event.preventDefault();
        pendingForm = form;

        if (confirmTitle) {
            confirmTitle.textContent = form.dataset.confirmTitle || 'Are you sure?';
        }

        if (confirmMessage) {
            confirmMessage.textContent = form.dataset.confirmMessage || 'Please confirm this action.';
        }

        if (confirmButton) {
            confirmButton.textContent = form.dataset.confirmLabel || 'Confirm';
            confirmButton.className = form.dataset.confirmVariant === 'danger' ? 'sk-btn-danger' : 'sk-btn-green';
        }

        confirmDialog.showModal();
    });
});

confirmButton?.addEventListener('click', () => {
    if (!pendingForm) {
        return;
    }

    pendingForm.dataset.confirmed = 'true';
    confirmDialog?.close();
    pendingForm.requestSubmit();
    pendingForm = null;
});
