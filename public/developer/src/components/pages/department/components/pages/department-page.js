import { SidebarMenuPage } from "@base-framework/ui/pages";
import { DepartmentSwitch } from "./department-switch.js";
import { Links } from "./links.js";

/**
 * This will create the base path.
 *
 * @constant
 * @type {string}
 */
const basePath = 'department';

/**
 * DepartmentPage
 *
 * This will create a department page.
 *
 * @returns {SidebarMenuPage}
 */
export const DepartmentPage = () => (
	new SidebarMenuPage({
		/**
		 * @member {string}	title
		 */
		title: 'Department',

		/**
		 * @member {string}	basePath
		 */
		basePath,

		/**
		 * @member {Array<object>} switch
		 */
		switch: DepartmentSwitch(basePath),

		/**
		 * @member {Array<object>} links
		 */
		links: Links(basePath)
	})
);

export default DepartmentPage;