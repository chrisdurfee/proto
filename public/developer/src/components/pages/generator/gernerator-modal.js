import { Model } from "@base-framework/base";

/**
 * GeneratorModel
 *
 * This model is used to handle the generator modal.
 *
 * @type {typeof Model}
 */
export const GeneratorModel = Model.extend({
	url: '/developer/api/gererator',

	xhr: {
		/**
		 * @type {string}
		 */
		objectType: 'resource',

		/**
		 * This will add the the resouce.
		 *
		 * @param {object} instanceParams - The instance parameters.
		 * @param {function} callBack - The callback function.
		 * @returns {object}
		 */
		add(instanceParams, callBack)
		{
			let params = 'type=' + this.model.get('type') +
					'&' + this.setupObjectData();

			return this._post('', params, instanceParams, callBack);
		}
	}
})