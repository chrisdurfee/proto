import { Div } from "@base-framework/atoms";
import { Model } from "@base-framework/base";
import { BlankPage } from "@base-framework/ui/pages";
import { UserModel } from "./models/user-model.js";
import { PageHeader } from "./page-header.js";
import { UserTable } from "./table/user-table.js";

/**
 * This will create the user page.
 *
 * @returns {BlankPage}
 */
export const UserPage = () =>
{
	/**
	 * @type {Model} data
	 */
	const data = new UserModel({
		filter: {

		}
	});

	/**
	 * @type {object} Props
	 */
	const Props =
	{
		data,

		/**
		 * This will update the user page when the url is
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
						UserTable(data)
					])
				])
			])
		])
	]);
};

export default UserPage;