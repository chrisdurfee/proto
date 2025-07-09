import { Model } from "@base-framework/base";

/**
 * LoginLogModel
 *
 * This model is used to handle the login log model.
 *
 * @type {typeof Model}
 */
export const LoginLogModel = Model.extend({
	url: '/api/auth/login-log/[[id]]',

	xhr: {
		/**
		 * Update a user's credentials.
		 *
		 * @param {object} instanceParams - The instance parameters.
		 * @param {function} callBack - The callback function.
		 */
		updateCredentials(instanceParams, callBack)
		{
			const data = this.model.get();
			let params = {
				username: data.username,
				password: data.password
			};

			return this._patch(`${data.id}/update-credentials`, params, instanceParams, callBack);
		},

		/**
		 * Verify a user's email.
		 *
		 * @param {object} instanceParams - The instance parameters.
		 * @param {function} callBack - The callback function.
		 */
		verifyEmail(instanceParams, callBack)
		{
			const data = this.model.get();
			let params = {
				token: instanceParams.token
			};

			return this._patch(`${data.id}/verify-email`, params, instanceParams, callBack);
		}
	}
});