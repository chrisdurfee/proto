import { Div, UseParent } from "@base-framework/atoms";
import { BlankPage } from "@base-framework/ui/pages";
import { UserModel } from "../../models/user-model.js";
import { LoginLogModel } from "./login-log-model.js";
import { LoginTable } from "./login-table.js";
import { PageHeader } from "./page-header.js";

/**
 * @type {object} Props
 */
const Props =
{
	setData()
	{
		return new UserModel({
			filter: {

			}
		});
	},

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
			//this.list.refresh();
		}
	}
};

/**
 * This will create the login time page.
 *
 * @returns {object}
 */
export const LoginTimePage = () =>
{
	const data = new LoginLogModel({
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
				//this.list.refresh();
			}
		}
	};

	return new BlankPage(Props, [
		Div({ class: 'grid grid-cols-1' }, [
			UseParent(({ route }) =>
			{
				// @ts-ignore
				data.userId = route.userId;
				return Div({ class: 'flex flex-auto flex-col pt-0 lg:space-y-12 w-full mx-auto 2xl:max-w-[1600px]' }, [
					PageHeader(),
					Div({ class: 'flex flex-auto flex-col space-y-4 lg:space-y-2' }, [
						Div({ class: 'flex flex-col overflow-x-auto' }, [
							LoginTable(data)
						])
					])
				]);
			})
		])
	]);
};

export default LoginTimePage;