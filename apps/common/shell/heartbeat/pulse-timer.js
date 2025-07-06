/**
 * PulseTimer
 *
 * This class is responsible for managing the timer mechanism
 *
 * It periodically checks if the user is still authenticated and
 * logs them out if their session has expired.
 */
export class PulseTimer
{
	/**
	 * Timer constructor
	 *
	 * Initializes the timer mechanism.
	 *
	 * @param {number} delay - The delay between timer ticks in milliseconds.
	 */
	constructor(delay = 0)
	{
		this.timer = null;
		this.delay = delay;
	}

	/**
	 * Starts the timer.
	 *
	 * @returns {void}
	 */
	start()
	{
		this.stop();

		const DELAY = this.delay;
		this.timer = window.setInterval(() =>
		{
			this.verify();
		}, 5000);
	}

	/**
	 * Stops the timer.
	 *
	 * @returns {void}
	 */
	stop()
	{
		window.clearInterval(this.timer);
	}

	/**
	 * Used to verify the user has access to the app.
	 *
	 * @returns {void}
	 */
	verify()
	{
		app.data.auth.xhr.pulse('', this.afterVerify.bind(this));
	}

	/**
	 * Called after the verification process is complete.
	 *
	 * @param {object} response
	 * @returns {void}
	 */
	afterVerify(response)
	{
		if (!response)
		{
			return;
		}

		if (response.user)
		{
			app.setUserData(response.user);
		}

		if (response.allowAccess === false)
		{
			app.appShell.signOut();
		}
	}
}