import { Model } from "@base-framework/base";

/**
 * UserRoleModel
 *
 * This model is used to handle the user role model.
 *
 * @type {typeof Model}
 */
export const UserAuthedDeviceModel = Model.extend({
	url: '/api/auth/[[userId]]/authed-device',

	xhr: {

	}
});