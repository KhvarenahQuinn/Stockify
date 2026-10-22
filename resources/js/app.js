import { initFlowbite } from 'flowbite';
import './bootstrap';
import './i18n';
import './auth-ui';
import './dashboard-charts';
import './dark-mode';
import './sidebar';
import './product-ui';
import './transaction-ui';
import './opname-ui';
import './toast';
import './settings-ui';
import './report-ui';

// Blade views re-bind data attributes after a validation redirect, since
// Flowbite only wires them once on window load.
window.initFlowbite = initFlowbite;
