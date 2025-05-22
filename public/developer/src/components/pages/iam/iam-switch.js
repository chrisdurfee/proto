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
export const IamSwitch = (basePath) => ([
	Page(`${basePath}`, 'Roles', () => import('./roles/role-page.js')),
	Page(`${basePath}/permissions`, 'Permissions', () => import('./permissions/permission-page.js')),
	Page(`${basePath}/organizations`, 'Organizations', () => import('./organizations/organizations-page.js')),
]);

export default IamSwitch;
