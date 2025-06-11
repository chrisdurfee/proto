import { Builder, router } from "@base-framework/base";
import { Configs } from "./configs.js";
import { setupServiceWorker } from "./service.js";
import { AppShell } from "./shell/app-shell.js";
import { setHtmlThemeBySettings } from "./theme.js";

/**
 * AppController
 *
 * This will setup the main app controller.
 *
 * @class
 */
export class AppController
{
	/**
	 * @member {object} router
	 */
	router = null;

	/**
	 * @member {object} appShell
	 */
	appShell = null;

	/**
	 * @member {object} data
	 */
	data = {};

	/**
	 * @member {object|null} root
	 */
	root = null;

	/**
	 * This will setup the main controller.
	 */
	constructor()
	{
		setHtmlThemeBySettings();
		this.setupService();
		this.setupRouter();
		this.setData();
	}

	/**
	 * This will set the data.
	 *
	 * @protected
	 * @returns {void}
	 */
	setData()
	{
		this.data = {};
	}

	/**
	 * This will setup the service worker.
	 *
	 * @protected
	 * @returns {void}
	 */
	setupService()
	{
		setupServiceWorker();
	}

	/**
	 * This will setup the router.
	 *
	 * @protected
	 * @return {void}
	 */
	setupRouter()
	{
		this.router = router;

		/**
		 * This will add the configs router settings
		 * to the router.
		 */
		const { baseUrl, title } = Configs.router;
		router.setup(baseUrl, title);
	}

	/**
	 * This will navigate to the uri.
	 *
	 * @param {string} uri
	 * @param {object} [data]
	 * @param {boolean} [replace=false]
	 * @return {void}
	 */
	navigate(uri, data, replace)
	{
		this.router.navigate(uri, data, replace);
	}

	/**
	 * This will setup the app shell.
	 *
	 * @protected
	 * @return {void}
	 */
	render()
	{
		const main = AppShell();
		this.appShell = Builder.render(main, document.body);

		/**
		 * This will create an alias to make accessing the app shell root panel easier.
		 *
		 * This property should be used to add popovers, modals, overlays, etc.
		 */
		this.root = this.appShell.panel;
	}

	/**
	 * This will add a notification.
	 *
	 * @param {object} props
	 * @returns {void}
	 */
	notify(props)
	{
		this.appShell.notifications.addNotice(props);
	}
}