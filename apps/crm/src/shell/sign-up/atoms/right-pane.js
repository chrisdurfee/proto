import { A, Div, Img } from '@base-framework/atoms';
import { Atom } from '@base-framework/base';
import { Button } from '@base-framework/ui/atoms';
import { SignUpForm } from './sign-up-form.js';

/**
 * This will create a logo.
 *
 * @param {object} props
 * @returns {object}
 */
const Logo = Atom((props) => (
	A({
		class: 'logo w-[24px] h-[24px] block md:hidden',
		href: './sign-up',
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
 * @function SignInNavigation
 * @description
 *  Button bar at the top-right that navigates to the "Sign In" step.
 *
 * @param {object} props
 * @param {Function} props.showStep - The callback to navigate steps.
 * @returns {object} A Div containing the "Sign in" button.
 */
const SignInNavigation = Atom(({ src }) =>
(
	Div({ class: 'p-4 flex justify-between' }, [
		Div({ class: 'flex items-center space-x-2' }, [
			Logo({
				src: src || '/images/logo.svg'
			})
		]),

		Button({
			variant: 'link',
			click: () => app.navigate('/')
		}, 'Sign in')
	])
));

/**
 * @function RightPane
 * @description
 *  Renders the top navigation (Sign In link) + sign-up form in a card on the right side.
 *
 * @returns {object} A Div component for the right panel.
 */
export const RightPane = Atom(() => (
	Div({ class: 'flex flex-auto flex-col' }, [
		// Top nav to go to sign-in
		SignInNavigation(),

		// Main sign-up card
		Div({
			class: 'flex flex-auto flex-col'
		}, [
			Div({ class: 'flex flex-auto flex-col justify-center items-center' }, [
				SignUpForm()
			])
		])
	])
));