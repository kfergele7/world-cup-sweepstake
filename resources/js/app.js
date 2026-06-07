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
