import { Div, Span } from "@base-framework/atoms";
import { Component, Jot } from "@base-framework/base";
import { Avatar, StatusIndicator } from "@base-framework/ui/molecules";
import { Configs } from "../../../configs.js";
import { UserLoginStatus } from "../../user-status/user-login-status.js";

/**
 * This will create the UserDetails molecule.
 *
 * @returns {object}
 */
const UserDetails = () => (
	Div([
		Span({ class: "text-sm text-foreground whitespace-nowrap" }, '[[name]]'),
		Span({ class: "text-xs text-muted-foreground capitalize whitespace-nowrap" }, ' - [[status]]'),
	])
);

/**
 * @type {UserLoginStatus} status
 */
const status = new UserLoginStatus(Configs.userStatusApi);

/**
 * NavigationAvatar
 *
 * This will create the NavigationAvatar molecule.
 *
 * @type {typeof Component} NavigationAvatar
 */
export const NavigationAvatar = Jot(
{
	/**
	 * This will set up the status tracker.
	 *
	 * @returns {void}
	 */
	after()
	{
		status.setup(app.data.user);
	},

	/**
	 * This will render the component.
	 *
	 * @returns {object}
	 */
	render()
	{
		return Div({ class: "flex items-center gap-4" }, [
			Div({ class: "relative" }, [
				// User Avatar
				Div({ class: "relative mx-2" }, [
					Avatar({
						src: '[[image]]',
						alt: '[[firstName]] [[lastName]]',
						watcherFallback: '[[firstName]] [[lastName]]',
						size: "sm",
					})
				]),
				StatusIndicator()
			]),
			UserDetails()
		]);
	},

	/**
	 * This will destroy the status tracker.
	 *
	 * @returns {void}
	 */
	destroy()
	{
		status.stop();
	}
});
