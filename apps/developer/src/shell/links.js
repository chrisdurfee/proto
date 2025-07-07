import { Icons } from '@base-framework/ui/icons';

/**
 * This will get the nav links.
 *
 * @return {Array<object>}
 */
export const Links = () => [
	{ label: 'Home', href: '/', icon: Icons.home, mobileOrder: 1, exact: true },
	{ label: 'Code', href: '/generator', icon: Icons.document.add, mobileOrder: 2 },
	{ label: 'Migrations', href: '/migrations', icon: Icons.stack, mobileOrder: 3 },
	{ label: 'Errors', href: '/errors', icon: Icons.bug, mobileOrder: 4 },
	{ label: 'Users', href: '/users', icon: Icons.user.group, mobileOrder: 5 },
	{ label: 'IAM', href: '/iam', icon: Icons.locked, mobileOrder: 6 },
	{ label: 'Docs', href: '/docs', icon: Icons.document.text, mobileOrder: 7 },
	{ label: 'Email', href: '/email', icon: Icons.at, mobileOrder: 8 },
];