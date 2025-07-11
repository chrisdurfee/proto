import { Icons } from "@base-framework/ui/icons";
import { Module } from '../module/module.js';

/**
 * This will set the routes for the module.
 *
 * @type {Array<object>} routes
 */
const routes = Module.convertRoutes(
[
	{ path: '/users/:userId/:page?*', import: () => import('./components/pages/users/user/user-page.js'), title: 'User' },
	{ path: '/users*', import: () => import('./components/pages/users/users-page.js'), title: 'Users' }
]);

/**
 * This will set the links for the module.
 *
 * @type {Array<object>} links
 */
const links =
[
	{ label: 'Users', href: 'users', icon: Icons.user.group, mobileOrder: 1 }
];

/**
 * This will create our module and add it to the app
 * modules.
 */
Module.create(
{
	/**
	 * @param {Array<object>} routes
	 */
	routes,

	/**
	 * This will get the options to create the app
	 * navigation.
	 *
	 * @param {Array<object>} links
	 */
	links
});