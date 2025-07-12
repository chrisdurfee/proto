import { Div, UseParent } from "@base-framework/atoms";
import { BlankPage } from "@base-framework/ui/pages";
import { LoginLogModel } from "./login-log-model.js";
import { LoginTable } from "./login-table.js";
import { PageHeader } from "./page-header.js";

/**
 * This will create the login time page.
 *
 * @returns {object}
 */
export const LoginTimePage = () =>
{
	const data = new LoginLogModel({
		filter: {

		},
		orderBy: {
			createdAt: 'DESC'
		}
	});

	/**
	 * @type {object} Props
	 */
	const Props =
	{
		data,
	};

	return new BlankPage(Props, [
		Div({ class: 'grid grid-cols-1 flex-auto' }, [
			UseParent(({ route }) =>
			{
				// @ts-ignore
				data.userId = route.userId;
				return Div({ class: 'flex flex-auto flex-col pt-0 lg:space-y-12 w-full mx-auto 2xl:max-w-[1600px]' }, [
					PageHeader(),
					Div({ class: 'flex flex-auto flex-col space-y-4 lg:space-y-2' }, [
						Div({ class: 'flex flex-auto flex-col overflow-x-auto' }, [
							LoginTable(data)
						])
					])
				]);
			})
		])
	]);
};

export default LoginTimePage;