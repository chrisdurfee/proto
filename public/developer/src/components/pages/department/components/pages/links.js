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
		href: `${path}/mission`,
		label: 'Mission'
	},
	{
		href: `${path}/personality`,
		label: 'Personality'
	},
	{
		group: 'Expectations',
		options: [
			{
				href: `${path}/goals`,
				label: 'Goals'
			},
			{
				label: 'Positions',
				options: [
				{
					label: 'CTO',
					href: `${path}/positions/cto`
				},
				{
					label: 'Director',
					href: `${path}/positions/director`
				},
				{
					label: 'Project Manager',
					href: `${path}/positions/project-manager`
				},
				{
					label: 'Product Manager',
					href: `${path}/positions/product-manager`
				},
				{
					label: 'Product Designer',
					href: `${path}/positions/product-designer`
				},
				{
					label: 'Software Engineer',
					href: `${path}/positions/software-engineer`
				},
				{
					label: 'DevOps Engineer',
					href: `${path}/positions/devops-engineer`
				},
				{
					label: 'SecOps Engineer',
					href: `${path}/positions/secops-engineer`
				},
				{
					label: 'Software Architect',
					href: `${path}/positions/software-architect`
				},
				{
					label: 'Data Scientist',
					href: `${path}/positions/data-scientist`
				},
				{
					label: 'System Engineer',
					href: `${path}/positions/system-engineer`
				},
				{
					label: 'QA Engineer',
					href: `${path}/positions/qa-engineer`
				}
			]
		}]
	},
	{
		group: 'Processes',
		options: [
			{
				href: `${path}/processes/end-to-end-development`,
				label: 'End-to-End Development'
			},
			{
				href: `${path}/processes/security/information-security-plan`,
				label: 'Information Security Plan'
			},
			{
				href: `${path}/processes/security/product-security`,
				label: 'Product Security'
			}
		]
	},
	{
		group: 'Skills',
		options: [
			{
				href: `${path}/skills/developer`,
				label: 'Developer'
			},
			{
				href: `${path}/skills/designer`,
				label: 'Designer'
			}
		]
	}
]);