import { Model } from "@base-framework/base";

/**
 * MigrationModel
 *
 * This model is used to handle the migration modal.
 *
 * @type {typeof Model}
 */
export const MigrationModel = Model.extend({
	url: 'https://proto.local/developer/api/migration',

	xhr: {

        /**
         * This will update the migration.
         *
         * @param {object} instanceParams
         * @param {function} callBack
         * @returns {object}
         */
		update(instanceParams, callBack)
        {
            let params = 'direction=' + instanceParams.direction;
            return this._post('', params, instanceParams, callBack);
        }
	}
});