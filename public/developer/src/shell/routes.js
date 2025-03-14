/**
 * This will create a dynamic route object.
 *
 * @param {string} uri
 * @param {function} callBack
 * @param {string} title
 * @param {boolean} [persist=true]
 * @returns {object}
 */
const DynamicRoute = (uri, callBack, title, persist = false) =>
{
	return {
		uri,
		import: callBack,
		title,
		persist
	};
};

/**
 * This will get the routes.
 *
 * @return {Array<object>}
 */
export const Routes = () => [
	DynamicRoute('/', () => import('../components/pages/home/components/pages/home-page.js'), 'Example'),

	/**
	 * Department routes
	 */
	DynamicRoute('/generator*', () => import('../components/pages/generator/generator-page.js'), 'Generator'),

	/**
	 * Migrations routes
	 */
	DynamicRoute('/migrations*', () => import('../components/pages/migrations/migration-page.js'), 'Migrations'),

	/**
	 * Errors routes
	 */
	DynamicRoute('/errors*', () => import('../components/pages/errors/error-page.js'), 'Errors'),

	/**
	 * Users routes
	 */
	DynamicRoute('/users*', () => import('../components/pages/users/user-page.js'), 'Users'),

	/**
	 * IAM routes
	 */
	DynamicRoute('/iam*', () => import('../components/pages/iam/iam-page.js'), 'IAM'),

	/**
	 * Docs routes
	 */
	DynamicRoute('/docs*', () => import('../components/pages/documentation/documentation-page.js'), 'Docs', true),
];