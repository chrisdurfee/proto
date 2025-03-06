import { Model } from "@base-framework/base";

/**
 * ErrorModel
 *
 * This model is used to handle the migration modal.
 *
 * @type {typeof Model}
 */
export const ErrorModel = Model.extend({
	url: 'https://proto.local/developer/api/error',

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