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
	{ label: 'Errors', href: '/developer/errors', icon: Icons.bug, mobileOrder: 4 }
];