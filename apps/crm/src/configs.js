/**
 * Configs
 *
 * This will set the app configs. Set any global configs here.
 *
 * @type {object} Configs
 */
export const Configs =
{
	APP_NAME: 'CRM',

	/**
	 * This will set the base URL for the app.
	 *
	 * @type {object} router
	 */
	router:
	{
		baseUrl: '/crm/',
		title: 'CRM'
	},

	/**
	 * This will enable the primary nav to be short
	 * without hovering.
	 *
	 * @type {boolean} useShortNav
	 */
	useShortNav: true,

	/**
	 * The push settings.
	 */
	push: {
		// TODO: Add your public key here
		publicId: 'BLQ5dFCQWbjg4SwqEe1NNYm31aD4MMNXsflFsCc-TxbipA1eTqtX_XdJqpCGq8tTbqYtSCVJFLB4d0D4FOtTrS4'
	},

	/**
	 * This will set the API endpoints for the user status.
	 *
	 * @type {string} userStatusApi
	 */
	userStatusApi: '/api/user/[[id]]/status',
};