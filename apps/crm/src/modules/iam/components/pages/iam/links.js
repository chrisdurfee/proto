/**
 * This will create the aside links.
 *
 * @param {string} path
 * @returns {Array<object>}
 */
export const Links = (path) => ([
	{
		href: `${path}`,
		label: 'Roles',
		exact: true
	},
	{
		href: `${path}/permissions`,
		label: 'Permissions'
	},
	{
		href: `${path}/organizations`,
		label: 'Organizations'
	}
]);
