import { Model } from "@base-framework/base";

/**
 * TableModel
 *
 * This model is used to handle the table modal.
 *
 * @type {typeof Model}
 */
export const TableModel = Model.extend(
{
    url: 'https://proto.local/developer/api/table',

    xhr: {
        /**
		 * @type {string}
		 */
        objectType: 'resource',

        /**
         * This will retrieve the columns of the table.
         *
         * @param {object} instanceParams - The instance parameters.
         * @param {function} callBack - The callback function.
         * @returns {object}
         */
        getColumns(instanceParams, callBack)
        {
            const params = 'connection=' + this.model.get('connection') +
                    '&tableName=' + this.model.get('tableName');

            const url = 'columns?' + params;

            return this._get(url, '', instanceParams, callBack);
        }
    }
});