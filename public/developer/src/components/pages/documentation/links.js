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
		href: `${path}/theme`,
		label: 'Theme',
		exact: true
	},
	{
		href: `${path}/icons`,
		label: 'Icons'
	},
	{
		group: 'Components',
		options:
		[
			{
				label: 'Buttons',
				href: `${path}/components/buttons`
			},
			{
				label: 'Badges',
				href: `${path}/components/badges`
			},
			{
				label: 'Tabs',
				href: `${path}/components/tabs`
			},
			{
				label: 'Modals',
				href: `${path}/components/modals`
			},
			{
				label: 'Notifications',
				href: `${path}/components/notifications`
			},
			{
				label: 'Dialogs',
				href: `${path}/components/dialogs`
			},
			{
				label: 'Cards',
				href: `${path}/components/cards`
			},
			{
				label: 'Calendars',
				href: `${path}/components/calendars`
			},
			{
				label: 'Avatars',
				href: `${path}/components/avatars`
			},
			{
				label: 'Inputs',
				href: `${path}/components/inputs`
			},
			{
				label: 'Forms',
				href: `${path}/components/forms`
			},
			{
				label: 'Alerts',
				href: `${path}/components/alerts`
			},
			{
				label: 'Comboboxes',
				href: `${path}/components/comboboxes`
			},
			{
				label: 'Lists',
				href: `${path}/components/lists`
			},
			{
				label: 'Data Tables',
				href: `${path}/components/data-tables`
			},
			{
				label: 'Breadcrumbs',
				href: `${path}/components/breadcrumbs`
			},
			{
				label: 'Navigations',
				href: `${path}/components/navigations`
			},
			{
				label: 'Progress Bars',
				href: `${path}/components/progress-bars`
			},
			{
				label: 'Skeletons',
				href: `${path}/components/skeletons`
			},
			{
				label: 'Tooltips',
				href: `${path}/components/tooltips`
			},
			{
				label: 'Dropdown Menus',
				href: `${path}/components/dropdown-menus`
			}
		]
	}
]);