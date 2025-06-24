import { Div, UseParent } from "@base-framework/atoms";
import { Overlay } from "@base-framework/ui/organisms";
import { UserModel } from "../models/user-model.js";
import { ContentSection } from "./content-section.js";
import { Sidebar } from "./sidebar.js";

/**
 * Props for the UserPage component.
 *
 * @typedef {object} Props
 */
const Props =
{
	/**
	 * Sets the context data for the user page.
	 *
	 * @param {object|null} context
	 * @returns {object}
	 */
	setContext(context)
	{
		return {
			data: new UserModel({
				user: null,
				loaded: false
			})
		};
	},

	/**
	 * Loads the user data after the component is set up.
	 *
	 * @returns {void}
	 */
	afterSetup()
	{
		const route = this.route;
		const data = this.context.data;
		data.id = this.route.userId;
		data.xhr.get('', (response) =>
		{
			if (!response || response.success === false)
			{
				data.set({ user: null, loaded: true });
				return;
			}

			const user = response.row || null;
			data.set({ user, loaded: true });
		});
	},

	/**
	 * Deletes the data when the component is destroyed.
	 *
	 * @returns {void}
	 */
	beforeDestroy()
	{
		this.context.data.delete();
		this.context.data.loaded = false;
	}
};

/**
 * UserPage
 *
 * Dynamically displays user details based on the `userId` from the route.
 *
 * @returns {Overlay}
 */
export const UserPage = () => (
	new Overlay(Props, [
		Div({ class: "flex flex-auto flex-col w-full" }, [
			Div({ class: "flex flex-auto flex-col gap-6 w-full" }, [
				Div({ class: 'flex flex-auto flex-col pt-0 sm:pt-2 lg:pt-0 lg:flex-row h-full' }, [
					UseParent(({ route }) => ([
						Sidebar({ userId: route.userId }),
						ContentSection({ userId: route.userId })
					]))
				])
			])
		])
	])
);

export default UserPage;