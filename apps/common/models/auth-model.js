import { Data, Encode, Model } from "@base-framework/base";
import { Env } from "../../crm/src/shell/env.js";

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
 * This will get the device id from local storage.
 *
 * @returns {string}
 */
const getDeviceId = () =>
{
	const KEY_NAME = 'device-id';
	const data = new Data();
	data.setKey(KEY_NAME);
	data.resume();

	// @ts-ignore
	let id = data.id ?? null;
	if (id === null)
	{
		id = createGuid();
		// @ts-ignore
		data.id = id;
		data.store();
	}
	return id;
};

/**
 * This will get the device params.
 *
 * @returns {string}
 */
const getEnvParams = () =>
{
	if (!Env)
	{
		return '';
	}

	const device = Encode.prepareJsonUrl({
		guid: getDeviceId(),
		mobile: Env.isMobile,
		brand: Env.brand,
		version: Env.version,
		platform: Env.platform,
		vendor: Env.vendor,
		touch: Env.isTouch
	});

	return device;
};

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
				username: encodeURI(data.username),
				password: encodeURIComponent(data.password),
				device: getEnvParams(),
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

		/**
		 * Get the CSRF token.
		 *
		 * @param {object} instanceParams - The instance parameters.
		 * @param {function} callBack - The callback function.
		 */
		getCsrfToken(instanceParams, callBack)
		{
			let params = "guid=" + GUID;

			return this._get('csrf-token', params, instanceParams, callBack);
		},

		/**
		 * Register a new user.
		 *
		 * @param {object} instanceParams - The instance parameters.
		 * @param {function} callBack - The callback function.
		 */
		register(instanceParams, callBack)
		{
			const data = this.model.get();
			let params = {
				...data,
				guid: GUID
			};

			return this._post('register', params, instanceParams, callBack);
		},

		/**
		 * Get MFA authentication code.
		 *
		 * @param {object} instanceParams - The instance parameters.
		 * @param {function} callBack - The callback function.
		 */
		getAuthCode(instanceParams, callBack)
		{
			const data = this.model.get();
			let params = {
				type: data.type,
				guid: GUID
			};

			return this._post('mfa/code', params, instanceParams, callBack);
		},

		/**
		 * Verify MFA authentication code.
		 *
		 * @param {object} instanceParams - The instance parameters.
		 * @param {function} callBack - The callback function.
		 */
		verifyAuthCode(instanceParams, callBack)
		{
			const data = this.model.get();
			let params = {
				code: data.code,
				guid: GUID
			};

			return this._post('mfa/verify', params, instanceParams, callBack);
		},

		/**
		 * Request a password reset.
		 *
		 * @param {object} instanceParams - The instance parameters.
		 * @param {function} callBack - The callback function.
		 */
		requestPasswordReset(instanceParams, callBack)
		{
			const data = this.model.get();
			let params = {
				email: data.email,
				guid: GUID
			};

			return this._post('password/request', params, instanceParams, callBack);
		},

		/**
		 * Validate a password reset request.
		 *
		 * @param {object} instanceParams - The instance parameters.
		 * @param {function} callBack - The callback function.
		 */
		validatePasswordRequest(instanceParams, callBack)
		{
			const data = this.model.get();
			let params = {
				requestId: data.requestId,
				userId: data.userId,
				guid: GUID
			};

			return this._post('password/verify', params, instanceParams, callBack);
		},

		/**
		 * Reset a user's password.
		 *
		 * @param {object} instanceParams - The instance parameters.
		 * @param {function} callBack - The callback function.
		 */
		resetPassword(instanceParams, callBack)
		{
			const data = this.model.get();
			let params = {
				requestId: data.requestId,
				userId: data.userId,
				password: data.password,
				guid: GUID
			};

			return this._post('password/reset', params, instanceParams, callBack);
		}
	}
});