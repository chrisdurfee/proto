import { A, Div, Img, P } from '@base-framework/atoms';
import { Atom } from '@base-framework/base';
import { Configs } from '../../../../src/configs.js';

/**
 * This will create a logo.
 *
 * @param {object} props
 * @returns {object}
 */
const Logo = Atom((props) => (
	A({
		class: 'logo w-[32px] h-[32px] m-[16px] block',
		href: './sign-up',
		...props
	}, [
		/**
		 * This will create the logo image.
		 */
		props.src && Img({
			src: props.src,
			alt: 'Logo',
			class: 'w-[32px] h-[32px]',

			/**
			 * This will hide the image if there is an error.
			 */
			error: (e) => e.target.style.display = 'none'
		})
	])
));

/**
 * @function LeftPane
 * @description
 *  Renders the dark left panel with branding and a testimonial.
 *
 * @returns {object} A Div component for the left panel.
 */
export const LeftPane = Atom((props) =>
(
	Div({ class: 'hidden md:flex flex-auto flex-col justify-between bg-accent p-8' }, [
		// Top Logo
		Div({ class: 'flex items-center space-x-2' }, [
			Logo({ src: props.src || '/images/logo.svg' }),
			Div({ class: 'text-lg font-bold' }, Configs.APP_NAME),
		]),

		// Testimonial
		Div({}, [
			P({ class: 'text-sm italic' }, '“Simplify your workflow with our intuitive tools.”'),
			P({ class: 'mt-2 text-sm font-semibold' }, 'Discover our user-friendly features.')
		])
	])
));