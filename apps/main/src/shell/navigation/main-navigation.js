import { Div } from "@base-framework/atoms";
import { Atom, Component, Jot } from "@base-framework/base";
import { Icons } from "@base-framework/ui/icons";
import { InlineNavigation } from "@base-framework/ui/organisms";
import { Configs } from "../../configs.js";
import { NavigationAvatar } from "./avatars/navigation-avatar.js";
import { MainHeader } from "./main-header.js";
import { ShortNavigation } from "./short/short-navigation.js";

/**
 * This will create the main navigation.
 *
 * @param {object} props
 * @param {array} children
 * @returns {object}
 */
const Navigation = Atom((props, children) => ({
	...props,
	class: 'main-navigation nav-container hidden lg:flex flex-col h-full z-10 overscroll-none',
	children
}));

/**
 * This will create the primary navigation.
 *
 * @param {object} props
 * @returns {object}
 */
const PrimaryNavigation = ({ options }) => (
	(Configs.useShortNav) ? new ShortNavigation({ options } ) : new InlineNavigation({ options })
);

/**
 * This will create the lower navigation.
 *
 * @returns {object}
 */
const LowerNavigation = () => (
	Div([
		new InlineNavigation({
			options: [
				{
					content: [

						/**
						 * This will create the navigation avatar.
						 */
						new NavigationAvatar({
							// @ts-ignore
							data: app.data.user
						})
					],
					// @ts-ignore
					callBack: () => app.navigate('settings/profile')
				},
				{
					href: 'settings',
					label: 'Settings',
					icon: Icons.cog.eight
				}
			]
		})
	])
);

/**
 * MainNavigation
 *
 * This will create the main navigation.
 *
 * @type {typeof Component}
 */
export const MainNavigation = Jot(
{
	/**
	 * This will render the component.
	 *
	 * @returns {object}
	 */
	render()
	{
		return Navigation([
			// @ts-ignore
			MainHeader({ callBack: () => this.state.toggle('pinned') }),
			Div({ class: 'nav-container flex flex-auto flex-col justify-between' }, [
				PrimaryNavigation({
					// @ts-ignore
					useShortNav: this.useShortNav || false,
					// @ts-ignore
					options: this.options
				}),
				LowerNavigation()
			])
		]);
	},

	/**
	 * This will link the pinned state to the app control.
	 *
	 * @returns {object}
	 */
	state()
	{
		return {
			pinned: { id: 'app-control' }
		};
	}
});