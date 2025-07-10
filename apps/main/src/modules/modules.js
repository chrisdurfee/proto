import "./imported-modules.js";
import { AppModules } from "./module/module.js";

/**
 * This will get the module settings.
 *
 * @param {Array<object>} modules
 * @returns {object}
 */
const getModuleSettings = (modules) =>
{
	let routes = [];
	let links = [];

	modules.forEach((module) =>
	{
		if (!module)
		{
			return;
		}

		const moduleRoutes = module.getRoutes() || [];
		routes.push(...moduleRoutes);

		const moduleLinks = module.getLinks() || [];
		links.push(...moduleLinks);
	});

	return {
		routes,
		links
	};
};

/**
 * This will add the modules to the app.
 *
 * @param {Array<object>} modules
 * @returns {object}
 */
export const AddModules = (modules) =>
{
	if (!modules || modules.length < 1)
	{
		return {};
	}

	return getModuleSettings(modules);
};

export const modules = AddModules(AppModules);
