import { Model } from "@base-framework/base";

/**
 * UserModel
 *
 * This model is used to handle the user model.
 *
 * @type {typeof Model}
 */
export const UserModel = Model.extend({
	url: '/api/user',

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
		}
	}
});