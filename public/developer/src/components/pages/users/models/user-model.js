import { Model } from "@base-framework/base";

/**
 * UserModel
 *
 * This model is used to handle the user model.
 *
 * @type {typeof Model}
 */
export const UserModel = Model.extend({
	url: 'https://proto.local/developer/api/user',

	xhr: {

		/**
		 * This will update the resolved status of the error.
		 *
		 * @param {object} instanceParams
		 * @param {function} callBack
		 * @returns {object}
		 */
		updateResolved(instanceParams, callBack)
		{
			const id = this.model.get('id');
			const resolved = this.model.get('resolved');

			let params = 'op=updateResolved' +
				'&id=' + id +
				'&resolved=' + resolved;

			return this._patch('', params, instanceParams, callBack);
		}
	}
});