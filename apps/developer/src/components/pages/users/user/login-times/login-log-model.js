import { Model } from "@base-framework/base";

/**
 * LoginLogModel
 *
 * This model is used to handle the login log model.
 *
 * @type {typeof Model}
 */
export const LoginLogModel = Model.extend({
	url: '/api/auth/[[userId]]/login-log',
});