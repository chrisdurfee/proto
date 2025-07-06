import { Builder, router } from "@base-framework/base";
import { getCsrfToken } from "../../common/csrf-token.js";
import { AuthModel } from "../../common/models/auth-model.js";
import { Configs } from "./configs.js";
import { setupServiceWorker } from "./service.js";
import { AppShell } from "./shell/app-shell.js";
import { UserData } from "./shell/models/user-data.js";
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
		this.getCsrfToken();
	}

	/**
	 * This will set the data.
	 *
	 * @protected
	 * @returns {void}
	 */
	setData()
	{
		/**
		 * This will set the user data to save to the local storage
		 * and resume the user session.
		 */
		const user = new UserData();
		user.setKey("user");
		user.resume();

		this.data = {
			user,
			auth: new AuthModel()
		};
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
	 * @returns {void}
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
	 * This will get the CSRF token.
	 *
	 * @returns {void}
	 */
	getCsrfToken()
	{
		// @ts-ignore
		getCsrfToken(this.data.auth);
	}

	/**
	 * This will navigate to the uri.
	 *
	 * @param {string} uri
	 * @param {object} [data]
	 * @param {boolean} [replace=false]
	 * @returns {void}
	 */
	navigate(uri, data, replace = false)
	{
		this.router.navigate(uri, data, replace);
	}

	/**
	 * This will render the app.
	 *
	 * @protected
	 * @returns {void}
	 */
	render()
	{
		const main = this.appShell = new AppShell();
		Builder.render(main, document.body);

		/**
		 * This will create an alias to make accessing the app shell root panel easier.
		 *
		 * This property should be used to add popovers, modals, overlays, etc.
		 */
		this.root = main.panel;
	}

	/**
	 * This will sign the user in.
	 *
	 * @returns {void}
	 */
	signIn(user)
	{
		this.appShell.state.isSignedIn = true;
		this.setUserData(user);
	}

	/**
	 * This will sign the user out.
	 *
	 * @returns {void}
	 */
	signOut()
	{
		this.data.auth.xhr.logout('', () =>
		{
			this.data.user
				.delete()
				.store();

			this.appShell.state.isSignedIn = false;

			window.location = Configs.router.baseUrl;
		});
	}

	/**
	 * This will set the user data.
	 *
	 * @protected
	 * @param {object|null} [data]
	 * @returns {void}
	 */
	setUserData(data = null)
	{
		if (!data)
		{
			return;
		}

		this.data.user
			.set(data)
			.store();
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