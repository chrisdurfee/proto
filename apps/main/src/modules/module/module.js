import { ModuleRoutes } from './module-routes.js';

/**
 * @type {Array<object>} AppModules
 */
export const AppModules = [];

/**
 * Module
 *
 * This will create a module to extend
 * to all child modules.
 *
 * @class
 */
export class Module
{
	/**
	 * This will create a module.
	 *
	 * @param {object} settings
	 */
	constructor({ links, routes } = {})
	{
		/**
		 * @member {Array<object>} links
		 */
		this.links = links;

		/**
		 * @member {Array<object>} links
		 */
		this.routes = routes;
	}

	/**
	 * This will create a route creator.
	 *
	 * @returns {object}
	 */
	static routeCreator()
	{
		return new ModuleRoutes();
	}

	/**
	 * This will convert the routes to the route creator.
	 *
	 * @param {Array<object>} routes
	 * @returns {Array<object>}
	 */
	static convertRoutes(routes)
	{
		const routeCreator = Module.routeCreator();
		return routes.map(route =>
		{
			if (route.import)
			{
				return routeCreator.load(
					route.path,
					route.import,
					route.title,
					route.preventScroll
				);
			}

			return routeCreator.add(
				route.path,
				route.component,
				route.title,
				route.preventScroll
			);
		});
	}

	/**
	 * This will get the module routes.
	 *
	 * @returns {Array<object>}
	 */
	getRoutes()
	{
		return this.routes;
	}

	/**
	 * This will get the module links.
	 *
	 * @returns {Array<object>}
	 */
	getLinks()
	{
		return this.links;
	}

	/**
	 * This will create a module.
	 *
	 * @param {object} settings
	 * @returns {object}
	 */
	static create(settings)
	{
		const module = new Module(settings);
		AppModules.push(module);
		return module;
	}
}