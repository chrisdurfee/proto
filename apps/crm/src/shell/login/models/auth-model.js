import { Model } from "@base-framework/base";

/**
 * This will create an id.
 *
 * @returns {string}
 */
function createGuid()
{
	let d = new Date().getTime();
	let d2 = (performance && performance.now && (performance.now() * 1000)) || 0;

	return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c)
	{
		let r = Math.random() * 16;
		if(d > 0)
		{
			r = (d + r) % 16 | 0;
			d = Math.floor(d/16);
		}
		else
		{
			r = (d2 + r) % 16 | 0;
			d2 = Math.floor(d2/16);
		}
		return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
	});
}

const GUID = createGuid();

/**
 * AuthModel
 *
 * This model is used to handle the authentication.
 *
 * @type {typeof Model}
 */
export const AuthModel = Model.extend({
	url: '/api/auth',

	xhr: {
        /**
         * Login the user.
         *
         * @param {object} instanceParams - The instance parameters.
         * @param {function} callBack - The callback function.
         */
        login(instanceParams, callBack)
		{
            const data = this.model.get();
			let params = {
                username: data.username,
                password: data.password,
                guid: GUID
            };

			return this._post('login', params, instanceParams, callBack);
		},

        /**
         * Logout the user.
         *
         * @param {object} instanceParams - The instance parameters.
         * @param {function} callBack - The callback function.
         */
        logout(instanceParams, callBack)
		{
			let params = "guid=" + GUID;

			return this._post('logout', params, instanceParams, callBack);
		},

        /**
         * Resume the user's session.
         *
         * @param {object} instanceParams - The instance parameters.
         * @param {function} callBack - The callback function.
         */
        resume(instanceParams, callBack)
        {
            let params = "guid=" + GUID;

            return this._post('resume', params, instanceParams, callBack);
        },

        /**
         * Pulse the user's session.
         *
         * @param {object} instanceParams - The instance parameters.
         * @param {function} callBack - The callback function.
         */
        pulse(instanceParams, callBack)
        {
            let params = "guid=" + GUID;

            return this._post('pulse', params, instanceParams, callBack);
        },

        getCsrfToken(instanceParams, callBack)
        {
            let params = "guid=" + GUID;

            return this._get('csrf-token', params, instanceParams, callBack);
        }
	}
});