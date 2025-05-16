import { Model } from "@base-framework/base";

/**
 * RolePermissionModel
 *
 * This model is used to handle the role permission model.
 *
 * @type {typeof Model}
 */
export const RolePermissionModel = Model.extend({
	url: 'https://proto.local/api/user/role/[[roleId]]/permission/[[permissionId]]',

	xhr: {

	}
});