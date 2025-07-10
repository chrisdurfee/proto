import { Div, OnState, Span } from "@base-framework/atoms";
import { Component, Jot } from "@base-framework/base";
import { Fieldset, Skeleton } from "@base-framework/ui/atoms";
import { Icons } from "@base-framework/ui/icons";
import { FormField, Toggle } from "@base-framework/ui/molecules";
import { RoleModel } from "../../iam/roles/models/role-model.js";
import { UserRoleModel } from "../models/user-role-model.js";

/**
 * This will add or remove the role for the user.
 *
 * @param {boolean} checked
 * @param {number} userId
 * @param {number} roleId
 */
const request = (checked, userId, roleId) =>
{
	const model = new UserRoleModel({
		userId,
		roleId
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
		}
	});
};

/**
 * This will create the role skeleton.
 *
 * @returns {object}
 */
export const RoleSkeleton = () => (
	Div({ class: "flex flex-col space-y-8" }, [
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
	])
);

/**
 * This will create the role fields.
 *
 * @param {object} props
 * @returns {object}
 */
const RoleFields = ({ hasRole, toggleRole }) => (
	Div({
		class: 'flex flex-col space-y-2',
		for: ['rows', (role) => new FormField(
			{
				name: role.name,
				description: role.description
			},
			[
				Div({ class: 'flex items-center space-x-2' }, [
					new Toggle({
						active: hasRole(role.name),
						change: (active, e) =>
						{
							// @ts-ignore
							toggleRole(role, active);
						}
					}),
					Span({ class: 'text-base' }, role.name)
				])
			])
		]
	})
);

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
	 * @param {string} roleName - The role name to check.
	 * @returns {boolean}
	 */
	hasRole(roleName)
	{
		// @ts-ignore
		const roles = this?.user?.roles || [];
		return roles.find(role => role.name === roleName) !== undefined;
	},

	/**
	 * This will toggle the role for the user.
	 *
	 * @param {object} role - The role to toggle.
	 * @param {boolean} checked - The checked state of the checkbox.
	 * @returns {void}
	 */
	toggleRole(role, checked)
	{
		const ROLE_KEY = 'roles';
		// @ts-ignore
		const index = this.user.getIndex(ROLE_KEY, 'id', role.id);

		if (checked && index === -1)
		{
			// @ts-ignore
			this.user.push(ROLE_KEY, role);
		}
		else if (!checked && index !== -1)
		{
			// @ts-ignore
			this.user.splice(ROLE_KEY, index);
		}

		// @ts-ignore
		request(checked, this.user.id, role.id);
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
					return RoleSkeleton();
				}

				return RoleFields({
					// @ts-ignore
					hasRole: this.hasRole.bind(this),
					// @ts-ignore
					toggleRole: this.toggleRole.bind(this)
				});
			})
		]);
	}
});