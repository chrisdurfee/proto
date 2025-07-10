import { A, Div, Header, Img } from "@base-framework/atoms";
import { Atom } from "@base-framework/base";
import { MobileUserAvatar } from "./avatars/mobile-user-avatar.js";

/**
 * This will create a logo.
 *
 * @param {object} props
 * @returns {object}
 */
const Logo = Atom((props) => (
	A({
		class: 'logo w-[24px] h-[24px] block',
		href: './',
		...props
	}, [

		/**
		 * This will create the logo image.
		 */
		props.src && Img({
			src: props.src,
			alt: 'Logo',
			class: 'w-[24px] h-[24px]',

			/**
			 * This will hide the image if there is an error.
			 */
			error: (e) => e.target.style.display = 'none'
		})
	])
));

/**
 * @type {number} prevScroll
 */
let prevScroll = 0;

/**
 * This will check when the document is scrolled.
 *
 * @param {Event} e
 * @param {object} parent
 * @returns {void}
 */
const scrollCheck = (e, parent) =>
{
	const currentScroll = window.scrollY;
	const HEADER_HEIGHT = 48;

	if (currentScroll > HEADER_HEIGHT && currentScroll > prevScroll)
	{
		parent.state.hideHeader = true;
	}
	else if (currentScroll <= HEADER_HEIGHT || currentScroll < prevScroll)
	{
		parent.state.hideHeader = false;
	}

	prevScroll = currentScroll;
};

/**
 * This will create the mobile header.
 *
 * @param {object} [props]
 * @returns {object}
 */
export const MobileHeader = (props = {}) => (
	Header(
		{
			class: 'mobile-header flex flex-row bg-background/80 backdrop-blur-md justify-between sm:hidden pb-3 px-6 fixed top-0 left-0 right-0 z-20 transition-all duration-200',

			/**
			 * This will add the hide header state to the parent
			 * component.
			 */
			addState: () => { hideHeader: false },

			/**
			 * This will add the hide header state to element
			 * when the hide-header state is true.
			 */
			onState: ['hideHeader', { 'hide-header': true }],

			/**
			 * This will check when the document is scrolled
			 * to update the hide header state.
			 */
			addEvent: ['scroll', window, scrollCheck]
		},
		[
			Logo({
				src: props.src || '/images/logo.svg'
			}),

			/**
			 * This will create the user avatar.
			 */
			Div({ class: 'flex' }, [
				// @ts-ignore
				new MobileUserAvatar({ data: app.data.user })
			])
		]
	)
);