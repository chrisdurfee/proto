import { Icons } from '@base-framework/ui/icons';

/**
 * This will get the nav links.
 *
 * @return {Array<object>}
 */
export const Links = () => [
	{ label: 'Home', href: './', icon: Icons.home, mobileOrder: 1, exact: true },
	{ label: 'Code', href: './generator', icon: Icons.document.add, mobileOrder: 2 }
];