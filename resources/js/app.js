import { createApp } from 'vue';
import DashboardStats from './Components/DashboardStats.vue';

const mount = document.getElementById('app');

if (mount?.dataset.page === 'dashboard') {
    createApp(DashboardStats, JSON.parse(mount.dataset.props || '{}')).mount(mount);
}
