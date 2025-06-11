import { Ajax, State } from "@base-framework/base";
import { Env } from "../env.js";

/**
 * This will convert a base64 string to a Uint8Array.
 *
 * @param {string} base64String
 * @returns {Uint8Array}
 */
const urlB64ToUint8Array = (base64String) =>
{
	const padding = '='.repeat((4 - base64String.length % 4) % 4);
	const base64 = (base64String + padding)
	.replace(/\-/g, '+')
	.replace(/_/g, '/');

	let rawData = window.atob(base64),
	outputArray = new Uint8Array(rawData.length);

	for (let i = 0; i < rawData.length; ++i)
	{
		outputArray[i] = rawData.charCodeAt(i);
	}
	return outputArray;
};

/**
 * This will set up the push state and default it to unsupported.
 *
 * @type {object} PUSH_STATE
 */
const PUSH_STATE = State.add('push', 'supported', false);

/**
 * Push
 *
 * This will handle the push notification.
 *
 * @class
 */
export class Push
{
	/**
	 * This will set up the push notification.
	 *
	 * @param {string} publicKey
	 * @param {object} serviceWorker
	 */
	constructor(publicKey, serviceWorker)
	{
		this.publicKey = publicKey;
		this.serviceWorker = serviceWorker;
	}

	/**
	 * This will set up the push notification.
	 *
	 * @returns {void}
	 */
	setup()
	{
		if (this.supported())
		{
			this.getSubscription();
		}
	}

	/**
	 * This will set up the state globally to allow the
	 * app to bind to the push states.
	 *
	 * @param {boolean} supported
	 * @returns {void}
	 */
	setupState(supported)
	{
		const target = this.state = PUSH_STATE;
		target.addAction('supported', supported);
		target.addAction('subscribed', false);
	}

	/**
	 * This will check if the push notification is supported.
	 *
	 * @returns {boolean}
	 */
	supported()
	{
		const supported = ('PushManager' in window);
		this.setupState(supported);

		return supported;
	}

	/**
	 * This will get the subscription.
	 *
	 * @returns {void}
	 */
	getSubscription()
	{
		const state = this.state;
		this.serviceWorker.pushManager.getSubscription()
		.then((subscription) =>
		{
			const subscribed = !(subscription === null);
			if (!subscribed)
			{
				this.subscribe();
			}

			state.set('subscribed', subscribed);
			this.updateSubscription(subscription);
		});
	}

	/**
	 * This will subscribe to the push notification.
	 *
	 * @returns {void}
	 */
	subscribe()
	{
		if (Env.isIos)
		{
			this.requestPermission();
			return;
		}

		this.handleSubscribe();
	}

	/**
	 * This will request the permission.
	 *
	 * @returns {Promise<void>}
	 */
	async requestPermission()
	{
		const result = await window.Notification.requestPermission();
		if (result !== 'granted')
		{
			return;
		}

		this.handleSubscribe();
	}

	/**
	 * This will handle the subscribe.
	 *
	 * @returns {void}
	 */
	handleSubscribe()
	{
		const state = this.state,
		publicKey = urlB64ToUint8Array(this.publicKey);

		this.serviceWorker.pushManager.subscribe({
			userVisibleOnly: true,
			applicationServerKey: publicKey
		})
		.then((subscription) =>
		{
			if (!subscription)
			{
				return false;
			}

			this.updateSubscription(subscription);
			state.set('subscribed', true);
		})
		.catch((err) =>
		{
			state.set('subscribed', false);
		});
	}

	/**
	 * This will unsubscribe from the push notification.
	 *
	 * @returns {void}
	 */
	unsubscribe()
	{
		const state = this.state;

		this.serviceWorker.pushManager.getSubscription()
		.then((subscription) =>
		{
			if (subscription)
			{
				subscription.unsubscribe();
				this.removeSubscription(subscription);
			}

			state.set('subscribed', false);
		})
		.catch((err) =>
		{
			state.set('subscribed', false);
		});
	}

	/**
	 * This will send the request.
	 *
	 * @param {string} option
	 * @param {object} subscription
	 * @returns {void}
	 */
	request(option, subscription)
	{
               // TODO: change this to the correct user id property if not "id"
		// and set correct params for your API
		// @ts-ignore
		const userId = app.data.user.id;
		let params = 'user=' + JSON.stringify({
			id: userId,
			option,
			subscription
		});

		// TODO: change this to the correct URL if not "/api/push"
		Ajax('/api/push', params);
	}

	/**
	 * This will remove the subscription.
	 *
	 * @param {object} subscription
	 * @returns {void}
	 */
	removeSubscription(subscription)
	{
		this.request('unsubscribe', subscription);
	}

	/**
	 * This will update the subscription.
	 *
	 * @param {object} subscription
	 * @returns {void}
	 */
	updateSubscription(subscription)
	{
		this.request('setup', subscription);
	}
}