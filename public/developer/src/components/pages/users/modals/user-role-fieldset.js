import { Div, OnState } from "@base-framework/atoms";
import { Component, Jot } from "@base-framework/base";
import { Checkbox, Fieldset, Skeleton } from "@base-framework/ui/atoms";
import { Icons } from "@base-framework/ui/icons";
import { FormField } from "@base-framework/ui/molecules";
import { RoleModel } from "../../iam/roles/models/role-model.js";
import { UserRoleModel } from "../models/user-role-model.js";

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
	 * This will check if the user has the role.
	 *
	 * @param {string} role - The role to check.
	 * @returns {boolean}
	 */
	hasRole(role)
	{
		// @ts-ignore
		const roles = this?.user?.roles || [];
		return roles.includes(role);
	},

	/**
	 * This will toggle the role for the user.
	 *
	 * @param {object} role - The role to toggle.
	 * @param {boolean} checked - The checked state of the checkbox.
	 */
	toggleRole(role, checked)
	{
		// @ts-ignore
		this.user.roles = this.user.roles.filter((r) => r !== role.name);

		if (checked)
		{
			// @ts-ignore
			this.user.roles.push(role);
		}

		const model = new UserRoleModel({
			// @ts-ignore
			userId: this.user.id,
			roleId: role.id
		});

		const method = checked ? 'add' : 'delete';
		model.xhr[method]('', (response) =>
		{
			if (!response || response.success === false)
			{
				app.notify({
					type: "destructive",
					title: "Error",
					description: `An error occurred while ${method === 'add' ? 'adding' : 'removing'} the role.`,
					icon: Icons.shield
				});
				return;
			}
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
								// @ts-ignore
								checked: this.hasRole(role.name),
								onChange: (checked, e) =>
								{
									// @ts-ignore
									this.toggleRole(role, checked);
								}
							})
						])
					]
				});
			})
		]);
	}
});