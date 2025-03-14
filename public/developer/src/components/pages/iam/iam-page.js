import { SidebarMenuPage } from "@base-framework/ui/pages";
import { IamSwitch } from "./iam-switch.js";
import { Links } from "./links.js";

/**
 * This will create the base path.
 *
 * @constant
 * @type {string}
 */
const basePath = 'docs';

/**
 * IamPage
 *
 * This will create an an iam page.
 *
 * @returns {SidebarMenuPage}
 */
export const IamPage = () => (
	new SidebarMenuPage({
		/**
		 * @member {string}	title
		 */
		title: 'IAM',

		/**
		 * @member {string}	basePath
		 */
		basePath,

		/**
		 * @member {Array<object>} switch
		 */
		switch: IamSwitch(basePath),

		/**
		 * @member {Array<object>} links
		 */
		links: Links(basePath)
	})
);

export default IamPage;