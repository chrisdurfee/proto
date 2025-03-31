import { Div } from "@base-framework/atoms";
import { BlankPage } from "@base-framework/ui/pages";
import { PermissionModel } from "./models/permission-model.js";
import { PageHeader } from "./page-header.js";
import { PermissionTable } from "./table/permission-table.js";

/**
 * This will create the permission page.
 *
 * @returns {object}
 */
export const PermissionPage = () =>
{
	const data = new PermissionModel({
		filter: 'all'
	});

	/**
	 * @type {object}
	 */
	const Props =
	{
		data,

		/**
		 * This will update the permission page when the url is
		 * updated.
		 *
		 * @returns {void}
		 */
		update()
		{
			if (this.list)
			{
				this.list.refresh();
			}
		}
	};
	return new BlankPage(Props, [
		Div({ class: 'grid grid-cols-1' }, [
			Div({ class: 'flex flex-auto flex-col p-6 pt-0 space-y-6 md:space-y-12 md:pt-6 lg:p-8 w-full mx-auto' }, [
				PageHeader(),
				Div({ class: 'flex flex-auto flex-col space-y-2 md:space-y-4' }, [
					Div({ class: 'flex flex-col overflow-x-auto' }, [
						PermissionTable(data)
					])
				])
			])
		])
	]);
};

export default PermissionPage;