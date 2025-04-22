import { Icons } from '@base-framework/ui/icons';

/**
 * This will get the nav links.
 *
 * @return {Array<object>}
 */
export const Links = () => [
	{ label: 'Home', href: '/developer/', icon: Icons.home, mobileOrder: 1, exact: true },
	{ label: 'Code', href: '/developer/generator', icon: Icons.document.add, mobileOrder: 2 },
	{ label: 'Migrations', href: '/developer/migrations', icon: Icons.stack, mobileOrder: 3 },
	{ label: 'Errors', href: '/developer/errors', icon: Icons.bug, mobileOrder: 4 },
	{ label: 'Users', href: '/developer/users', icon: Icons.user.group, mobileOrder: 5 },
	{ label: 'IAM', href: '/developer/iam', icon: Icons.locked, mobileOrder: 6 },
	{ label: 'Docs', href: '/developer/docs', icon: Icons.document.text, mobileOrder: 7 },
	{ label: 'Email', href: '/developer/email', icon: Icons.at, mobileOrder: 8 },
];