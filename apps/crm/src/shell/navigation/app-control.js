import { Atom, Component, Data } from "@base-framework/base";
import { Configs } from "../../configs.js";
import { MainNavigation } from "./main-navigation.js";
import { getMobileOptions } from "./mobile-options.js";
import { MobileNavigation } from "./mobile/mobile-navigation.js";

/**
 * This will get the hover class.
 *
 * @returns {string}
 */
const getHoverClass = () => Configs.useShortNav ? '' : 'lg:max-w-[330px] lg:hover:w-[330px] lg:transition-[width] lg:duration-200 lg:delay-100 lg:ease-in-out lg:will-change-[width]';

/**
 * This will create the app container.
 *
 * @param {object} props
 * @param {array} children
 * @returns {object}
 */
const AppContainer = Atom((props, children) => ({
	...props,
	class: `app-nav-container bg-background/80 backdrop-blur-md fixed sm:top-0 bottom-0 left-0 w-full lg:w-[64px]
	sm:h-full z-10 lg:z-20 lg:overflow-y-auto overflow-x-hidden shadow-md border-t sm:border-r sm:border-t-0 lg:border-r
	${getHoverClass()}`,
	children
}));

/**
 * AppControl
 *
 * This will create the app control.
 *
 * @class
 * @extends Component
 */
export class AppControl extends Component
{
	/**
	 * This will declare the properties of the component.
	 *
	 * @returns {void}
	 */
	declareProps()
	{
		/**
		 * @member {number|null} timer
		 */
		this.timer = null;
	}

	/**
	 * This will set the app controll state to be stored in the local storage.
	 *
	 * @returns {Data}
	 */
	afterSetup()
	{
		/**
		 * This will set up the pinned data to allow it to
		 * be stored in the local storage.
		 */
		const storageKey = 'pinned';

		// @ts-ignore
		const data = this.state;
		data.setKey(storageKey);

		/**
		 * This will resume the data from the local storage.
		 */
		data.resume();
		return data;
	}

	/**
	 * This will render the component.
	 *
	 * @returns {object}
	 */
	render()
	{
		const callBack = this.ignoreHover.bind(this);
		// @ts-ignore
		const mobileOptions = getMobileOptions(this.options, callBack);

		return AppContainer(
			{
				onState: [
					['ignoreHover', {
						ignoreHover: true
					}],
					['pinned', {
						pinned: true
					}]
				],
				mouseleave: this.removeIgnore.bind(this)
			},
			[
				/**
				 * This will create a navigation for the main and mobile navigation.
				 */
				// @ts-ignore
				new MainNavigation({ options: this.options }),
				new MobileNavigation({ options: mobileOptions })
			]
		);
	}

	/**
	 * This will ignore the hover.
	 *
	 * @returns {void}
	 */
	ignoreHover()
	{
		// @ts-ignore
		this.state.ignoreHover = true;

		const DELAY_MILLISECONDS = 100;
		window.setTimeout(() => this.removeIgnore(), DELAY_MILLISECONDS);
	}

	/**
	 * This will remove the ignore.
	 *
	 * @returns {void}
	 */
	removeIgnore()
	{
		window.clearTimeout(this.timer);

		const DELAY_MILLISECONDS = 400;
		// @ts-ignore
		const callBack = () => this.state.ignoreHover = false;
		this.timer = window.setTimeout(callBack, DELAY_MILLISECONDS);
	}

	/**
	 * This will setup the states.
	 *
	 * @returns {object}
	 */
	setupStates()
	{
		/**
		 * This will set a global state id for the app control.
		 */
		this.stateTargetId = 'app-control';

		return {
			ignoreHover: false,

			/**
			 * This will add a callback to store the pinned state
			 * in the local storage when it changes.
			 */
			pinned: {
				state: false,
				// @ts-ignore
				callBack: () => this.state.store()
			}
		};
	}
}