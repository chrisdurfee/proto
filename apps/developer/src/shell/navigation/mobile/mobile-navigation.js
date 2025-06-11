import { Button, I, Li, Nav, Span, Ul } from "@base-framework/atoms";
import { Component, Jot } from "@base-framework/base";
import { Icons } from "@base-framework/ui/icons";
import { MobileLink } from "./mobile-link.js";
import { PopOverContainer } from "./popover-container.js";

/**
 * AdditionalOptionsButton
 *
 * A reusable button to trigger additional options in a navigation menu.
 *
 * @param {object} props
 * @returns {object}
 */
export const AdditionalOptionsButton = ({ click }) => (
	Li({
		class: 'mobile-link flex flex-auto items-center justify-center cursor-pointer max-h-[80px]',
		click
	}, [
		Button({ class: 'p-[4px] flex flex-col items-center justify-center', onState: ['open', { active: true }] }, [
			I({ class: 'flex items-center justify-center py-[2px] px-3 rounded-md', html: Icons.ellipsis.vertical }),
			Span({ class: 'text-[10px]' }, 'More')
		])
	])
);

/**
 * @constant {number} MAX_PRIMARY_OPTIONS
 */
const MAX_PRIMARY_OPTIONS = 5;

/**
 * This will separate the options.
 *
 * @param {array} options
 * @returns {object}
 */
const separateOptions = (options) =>
{
	const primaryOptions = options.slice(0, MAX_PRIMARY_OPTIONS);
	const additionalOptions = options.slice(MAX_PRIMARY_OPTIONS);
	return { primaryOptions, additionalOptions };
};

/**
 * MobileNavigation
 *
 * A mobile navigation component that displays a list of links in a dropdown.
 *
 * @type {typeof Component}
 */
export const MobileNavigation = Jot(
{
	/**
	 * Initializes the component state.
	 *
	 * @returns {object}
	 */
	state()
	{
		return {
			open: false
		};
	},

	/**
	 * Renders the Dropdown component.
	 *
	 * @returns {object}
	 */
	render()
	{
		// @ts-ignore
		const options = this.options || [];

		/**
		 * Add a click event to dismiss the dropdown when an option is selected.
		 */
		// @ts-ignore
		const callback = () => this.state.open = false;
		options.map(option => option.click = callback);

		/**
		 * Separate the options into primary options and additional options.
		 */
		const { primaryOptions, additionalOptions } = separateOptions(options);

		return Nav({ class: 'mobile-navigation flex flex-auto flex-col w-full h-full lg:hidden z-50' }, [
			Ul({ class: 'flex flex-auto m-0 p-0 list-none sm:flex-col' }, [

				// Add the primary options and the additional options button.
				...primaryOptions.map(option => MobileLink(option)),

				// Add the additional options button if there are additional options.
				additionalOptions.length > 0 && AdditionalOptionsButton({
					// @ts-ignore
					click: () => this.state.toggle('open')
				})
			]),
			PopOverContainer({ options: additionalOptions })
		]);
	}
});