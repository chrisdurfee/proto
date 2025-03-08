/**
 * This will create a page dynamically.
 *
 * @param {string} url The URL or path this page should match
 * @param {string} title A descriptive title for the page
 * @param {function|Promise} importCallback A function returning the dynamic import
 * @returns {object}
 */
const Page = (url, title, importCallback) => ({
	uri: url,
	title,
	import: importCallback,
});

/**
 * This will create the documentation switch.
 *
 * @param {string} basePath
 * @returns {Array<object>}
 */
export const DocumentationSwitch = (basePath) => ([
	Page(`${basePath}`, 'Introduction', import('./introduction/intro-page.js')),
	Page(`${basePath}/get-started`, 'Get Started', () => import('./get-started/get-started-page.js')),
	Page(`${basePath}/theme`, 'Theme', () => import('./theme/theme-page.js')),
	Page(`${basePath}/icons`, 'Icons', () => import('./icons/icon-page.js')),
	Page(`${basePath}/components/buttons*`, 'Buttons', () => import('./components/buttons/button-page.js')),
	Page(`${basePath}/components/badges*`, 'Badges', () => import('./components/badge-page.js')),
	Page(`${basePath}/components/tabs*`, 'Tabs', () => import('./components/tab-page.js')),
	Page(`${basePath}/components/modals*`, 'Modals', () => import('./components/modal-page.js')),
	Page(`${basePath}/components/notifications*`, 'Notifications', () => import('./components/notification-page.js')),
	Page(`${basePath}/components/dialogs*`, 'Modals', () => import('./components/dialog-page.js')),
	Page(`${basePath}/components/cards*`, 'Cards', () => import('./components/cards/card-page.js')),
	Page(`${basePath}/components/calendars*`, 'Calendars', () => import('./components/calendar-page.js')),
	Page(`${basePath}/components/avatars*`, 'Avatars', () => import('./components/avatar-page.js')),
	Page(`${basePath}/components/inputs*`, 'Inputs', () => import('./components/input-page.js')),
	Page(`${basePath}/components/forms*`, 'Forms', () => import('./components/form-page.js')),
	Page(`${basePath}/components/alerts*`, 'Alerts', () => import('./components/alert-page.js')),
	Page(`${basePath}/components/comboboxes*`, 'Comboboxes', () => import('./components/comboxbox-page.js')),
	Page(`${basePath}/components/lists*`, 'Lists', () => import('./components/list-page.js')),
	Page(`${basePath}/components/data-tables*`, 'Data Tables', () => import('./components/data-table/data-table-page.js')),
	Page(`${basePath}/components/breadcrumbs*`, 'Breadcrumbs', () => import('./components/breadcrumb-page.js')),
	Page(`${basePath}/components/navigations*`, 'Navigations', () => import('./components/navigation-page.js')),
	Page(`${basePath}/components/progress-bars*`, 'Progress Bars', () => import('./components/progress-bar-page.js')),
	Page(`${basePath}/components/skeletons*`, 'Skeletons', () => import('./components/skeleton-page.js')),
	Page(`${basePath}/components/tooltips*`, 'Tooltips', () => import('./components/tooltip-page.js')),
	Page(`${basePath}/components/dropdown-menus*`, 'Dropdown Menus', () => import('./components/dropdown-menu-page.js')),
]);

export default DocumentationSwitch;