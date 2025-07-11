import { Icons } from "@base-framework/ui/icons";
import { Module } from '../module/module.js';

/**
 * This will set the routes for the module.
 *
 * @type {Array<object>} routes
 */
const routes = Module.convertRoutes(
[
	{ path: '/iam*', import: () => import('./components/pages/iam/iam-page.js'), title: 'IAM' }
]);

/**
 * This will set the links for the module.
 *
 * @type {Array<object>} links
 */
const links =
[
	{ label: 'IAM', href: 'iam', icon: Icons.locked, mobileOrder: 2 }
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