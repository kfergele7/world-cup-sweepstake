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

document.querySelectorAll('[data-bulk-pot-form]').forEach((form) => {
    const count = form.querySelector('[data-selected-count]');
    const clearButton = form.querySelector('[data-clear-selection]');
    const checkboxes = [...form.querySelectorAll('input[type="checkbox"][name="team_ids[]"]')];

    const updateCount = () => {
        const selectedCount = checkboxes.filter((checkbox) => checkbox.checked).length;

        if (count) {
            count.textContent = selectedCount;
        }

        if (clearButton) {
            clearButton.disabled = selectedCount === 0;
        }
    };

    checkboxes.forEach((checkbox) => checkbox.addEventListener('change', updateCount));
    clearButton?.addEventListener('click', () => {
        checkboxes.forEach((checkbox) => {
            checkbox.checked = false;
        });

        updateCount();
    });
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

const tabActivators = new Map();

document.querySelectorAll('[data-tabs]').forEach((tabs) => {
    const buttons = [...tabs.querySelectorAll('[data-tab-target]')];
    const panels = [...tabs.querySelectorAll('[data-tab-panel]')];

    if (buttons.length === 0 || panels.length === 0) {
        return;
    }

    const tabNames = panels.map((panel) => panel.dataset.tabPanel);
    const setActiveStyles = (button, isActive) => {
        button.classList.toggle('border-brand-navy', isActive);
        button.classList.toggle('bg-brand-navy', isActive);
        button.classList.toggle('text-white', isActive);
        button.classList.toggle('hover:border-brand-navy', isActive);
        button.classList.toggle('hover:bg-brand-navy', isActive);
        button.classList.toggle('hover:text-white', isActive);
        button.classList.toggle('border-brand-border', !isActive);
        button.classList.toggle('bg-white', !isActive);
        button.classList.toggle('text-brand-muted', !isActive);
        button.classList.toggle('hover:border-brand-blue/40', !isActive);
        button.classList.toggle('hover:bg-brand-blue/5', !isActive);
        button.classList.toggle('hover:text-brand-navy', !isActive);
        button.setAttribute('aria-current', isActive ? 'page' : 'false');
    };
    const activate = (tabName, updateHash = false) => {
        const nextTabName = tabNames.includes(tabName) ? tabName : (tabs.dataset.defaultTab || tabNames[0]);

        panels.forEach((panel) => {
            panel.classList.toggle('hidden', panel.dataset.tabPanel !== nextTabName);
        });

        buttons.forEach((button) => {
            setActiveStyles(button, button.dataset.tabTarget === nextTabName);
        });

        if (updateHash) {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', nextTabName);
            url.hash = '';
            window.history.replaceState(null, '', url);
        }
    };
    const initialTab = new URL(window.location.href).searchParams.get('tab')
        || window.location.hash.replace('#', '')
        || tabs.dataset.activeTab
        || tabs.dataset.defaultTab
        || tabNames[0];

    tabActivators.set(tabs, activate);
    activate(initialTab);

    buttons.forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            activate(button.dataset.tabTarget, true);
        });
    });

    window.addEventListener('hashchange', () => {
        activate(window.location.hash.replace('#', ''));
    });
});

document.querySelectorAll('[data-scroll-to]').forEach((button) => {
    button.addEventListener('click', () => {
        const target = document.querySelector(button.dataset.scrollTo || '');
        const panel = target?.closest('[data-tab-panel]');
        const tabs = panel?.closest('[data-tabs]');
        const activate = tabs ? tabActivators.get(tabs) : null;

        if (panel && activate) {
            activate(panel.dataset.tabPanel, true);
        }

        target?.scrollIntoView({
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
