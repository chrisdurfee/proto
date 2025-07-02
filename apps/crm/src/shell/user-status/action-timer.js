import { Events, State } from "@base-framework/base";
import { Timer } from "@base-framework/organisms";
import { APP_STATE, STATES, STATE_ATTR } from "./state.js";

/**
 * Debounce
 *
 * Creates a debounced function that delays the execution of the callback
 * until after a specified wait time has elapsed since the last call.
 *
 * @param {function} callBack - The function to debounce.
 * @param {number} wait - The number of milliseconds to wait.
 * @returns {function} A debounced version of the callback.
 */
const debounce = function(callBack, wait = 250)
{
	let timer = null;

	return function(...args)
	{
		window.clearTimeout(timer);
		timer = window.setTimeout(() =>
		{
			callBack.apply(null, args);
		}, wait);
	};
};

/**
 * ActionTimer
 *
 * Tracks user activity and updates their status based on app usage.
 *
 * @type {object} ActionTimer
 * @constant
 */
export const ActionTimer =
{
	/**
	 * Sets up the ActionTimer by initializing the state and activity events.
	 *
	 * @param {object} userData - The user data object.
	 * @returns {void}
	 */
	setup(userData)
	{
		this.userData = userData;
		const DURATION = 900000; // 15 minutes of inactivity triggers "away"
		this.timer = new Timer(DURATION, this.setAsAway.bind(this));

		this.setupEvents();
		this.setupState();
	},

	/**
	 * Initializes the global state for tracking user status and
	 * binds it to app user data.
	 *
	 * @returns {void}
	 */
	setupState()
	{
		const state = (this.state = State.add(APP_STATE, STATE_ATTR));

		// Watch for changes to the user's status in the state
		state.on(STATE_ATTR, (value) =>
		{
			this.checkMode(value);
		});

		// Set the default state value from user data
		const user = this.userData;
		const USER_STATUS = user.status || STATES.OFFLINE;
		state.set(STATE_ATTR, USER_STATUS);

		// Link state and user data for two-way binding
		user.link(state, STATE_ATTR, 'status');
	},

	/**
	 * Starts or stops tracking based on the user's current state.
	 *
	 * @param {string} state - The current state of the user.
	 * @returns {void}
	 */
	checkMode(state)
	{
		switch (state)
		{
			case STATES.ONLINE:
			case STATES.AWAY:
				this.start();
				break;
			default:
				// If user is offline or busy, we stop the timer by default
				this.stop();
				break;
		}
	},

	/**
	 * Configures activity tracking events (e.g., mouse movements, key presses).
	 *
	 * @returns {void}
	 */
	setupEvents()
	{
		const reset = debounce(this.reset.bind(this));

		const events = [
			[['mousemove', 'mousedown', 'keyup', 'touchend'], document, reset],
			[['pageshow', 'focus'], window, reset],
		];

		this.on = () =>
		{
			for (let i = 0; i < events.length; i++)
			{
				const [eventNames, target, handler] = events[i];
				// @ts-ignore
				Events.on(eventNames, target, handler);
			}
		};

		this.off = () =>
		{
			for (let i = 0; i < events.length; i++)
			{
				const [eventNames, target, handler] = events[i];
				// @ts-ignore
				Events.off(eventNames, target, handler);
			}
		};
	},

	/**
	 * Updates the user's state.
	 *
	 * @param {string} state - The new state to set.
	 * @returns {void}
	 */
	setState(state)
	{
		this.state.set(STATE_ATTR, state);
	},

	/**
	 * Sets the user's status to "away."
	 *
	 * @returns {void}
	 */
	setAsAway()
	{
		this.setState(STATES.AWAY);
	},

	/**
	 * Sets the user's status to "online."
	 *
	 * @returns {void}
	 */
	setAsOnline()
	{
		this.setState(STATES.ONLINE);
	},

	/**
	 * Starts tracking activity by enabling events and starting the timer.
	 *
	 * @returns {void}
	 */
	start()
	{
		this.on();
		this.timer.start();
	},

	/**
	 * Resets the activity timer and sets the user's status to "online."
	 *
	 * @returns {void}
	 */
	reset()
	{
		// If you want to preserve "busy," add a check before setting ONLINE.
		this.setAsOnline();
		this.timer.start();
	},

	/**
	 * Stops tracking activity by disabling events and stopping the timer.
	 *
	 * @returns {void}
	 */
	stop()
	{
		this.off();
		this.timer.stop();
	},
};