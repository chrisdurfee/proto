import { Model } from "@base-framework/base";

/**
 * UserRoleModel
 *
 * This model is used to handle the user role model.
 *
 * @type {typeof Model}
 */
export const UserRoleModel = Model.extend({
	url: 'https://proto.local/developer/api/user/[[userId]]/role/[[roleId]]',

	xhr: {

	}
});