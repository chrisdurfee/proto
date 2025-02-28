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
	DynamicRoute('/department/:page?*', () => import('../components/pages/department/components/pages/department-page.js'), 'Department'),
];