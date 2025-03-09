/**
 * This will create the aside links.
 *
 * @param {string} path
 * @returns {Array<object>}
 */
export const Links = (path) => ([
	{
		href: `${path}`,
		label: 'Introduction',
		exact: true
	},
	{
		href: `${path}/get-started`,
		label: 'Get Started'
	},
	{
		href: `${path}/modules`,
		label: 'Modules'
	},
	{
		label: 'Http',
		href: `${path}/http`
	},
	{
		label: 'API',
		href: `${path}/api`
	},
	{
		label: 'Auth',
		href: `${path}/auth`
	},
	{
		label: 'Controllers',
		href: `${path}/controllers`
	},
	{
		label: 'Events',
		href: `${path}/events`
	},
	{
		label: 'File Storage',
		href: `${path}/file-storage`
	},
	{
		label: 'Migrations',
		href: `${path}/migrations`
	},
	{
		label: 'Models',
		href: `${path}/models`
	},
	{
		label: 'Services',
		href: `${path}/services`
	},
	{
		label: 'Storage',
		href: `${path}/storage`
	},
	{
		label: 'Tests',
		href: `${path}/tests`
	},
	{
		label: 'Dispatch',
		href: `${path}/dispatch`
	}
]);
