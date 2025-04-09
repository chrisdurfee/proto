import { Div, OnState } from "@base-framework/atoms";
import { Component, Jot } from "@base-framework/base";
import { Checkbox, Fieldset, Skeleton } from "@base-framework/ui/atoms";
import { FormField } from "@base-framework/ui/molecules";
import { RoleModel } from "../../iam/roles/models/role-model.js";

/**
 * UserRoleFieldset
 *
 * Displays the skeleton placeholder while the roles loads.
 *
 * @type {typeof Component}
 */
export const UserRoleFieldset = Jot(
{
	/**
	 * This will set the default data for the component.
	 *
	 * @returns {object}
	 */
	setData()
	{
		return new RoleModel({
			rows: []
		});
	},

	/**
	 * This will set up the state.
	 *
	 * @returns {object}
	 */
	state: { loaded: false },

	/**
	 * This will fetch the data from the server.
	 *
	 * @returns {void}
	 */
	fetch()
	{
		// @ts-ignore
		this.data.xhr.all('', (response) =>
		{
			// @ts-ignore
			this.state.loaded = true;

			if (!response || response.success === false)
			{
				return;
			}

			// @ts-ignore
			this.data.rows = response.rows;
		});
	},

	/**
	 * This will render the UserRoleFieldset component.
	 *
	 * @returns {object}
	 */
	render()
	{
		// @ts-ignore
		this.fetch();

		return Fieldset({ legend: "User Roles" }, [
			OnState('loaded', (loaded) =>
			{
				if (!loaded)
				{
					return Div({ class: "flex flex-col space-y-8" }, [
						...[1, 2, 3, 4].map(() =>
							Div({ class: "flex flex-col space-y-2" }, [
								Div({ class: "flex items-center space-x-2" }, [
									Skeleton({
										shape: "circle",
										width: "w-5",
										height: "h-5"
									}),
									Skeleton({
										width: "w-32",
										height: "h-4"
									})
								]),
								Skeleton({
									width: "w-40",
									height: "h-3"
								})
							])
						)
					]);
				}

				return Div({
					class: 'flex flex-col space-y-2',
					for: [
						'rows',
						(role) => new FormField(
						{
							name: role.name,
							description: role.description
						},
						[
							new Checkbox({
								label: role.name,
								required: false,
								onChange: (checked, e) =>
								{
									// handle checked change if needed
								}
							})
						])
					]
				});
			})
		]);
	}
});