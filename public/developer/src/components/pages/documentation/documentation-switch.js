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
	Page(`${basePath}`, 'Introduction', () => import('./introduction/intro-page.js')),
	Page(`${basePath}/get-started`, 'Get Started', () => import('./get-started/get-started-page.js')),
	Page(`${basePath}/http`, 'Http', () => import('./http/http-page.js')),
	Page(`${basePath}/api`, 'API', () => import('./api/api-page.js')),
	Page(`${basePath}/auth`, 'Auth', () => import('./auth/auth-page.js')),
	Page(`${basePath}/controllers`, 'Controllers', () => import('./controllers/controllers-page.js')),
	Page(`${basePath}/events`, 'Events', () => import('./events/events-page.js')),
	Page(`${basePath}/file-storage`, 'File Storage', () => import('./file-storage/file-storage-page.js')),
	Page(`${basePath}/migrations`, 'Migrations', () => import('./migrations/migrations-page.js')),
	Page(`${basePath}/models`, 'Models', () => import('./models/models-page.js')),
	Page(`${basePath}/services`, 'Services', () => import('./services/services-page.js')),
	Page(`${basePath}/storage`, 'Storage', () => import('./storage/storage-page.js')),
	Page(`${basePath}/tests`, 'Tests', () => import('./tests/tests-page.js')),
	Page(`${basePath}/dispatch`, 'Dispatch', () => import('./dispatch/dispatch-page.js')),
]);

export default DocumentationSwitch;
