import { A, Div } from "@base-framework/atoms";
import { Atom } from "@base-framework/base";
import { Icons } from '@base-framework/ui/icons';

/**
 * This will create a logo.
 *
 * @param {object} props
 * @returns {object}
 */
const Logo = Atom((props, children) => (
	A({
		class: 'logo w-[32px] h-[32px] m-[16px] block',
		href: './',
		...props,
		children
	})
));

/**
 * This will create a pin icon.
 *
 * @param {boolean} pinned
 * @returns {object}
 */
const PinIcon = (pinned) => (pinned)? Icons.unlocked : Icons.locked;

/**
 * This will create a pin button
 *
 * @param {object} props
 * @param {array} children
 * @returns {object}
 */
const PinButton = Atom((props, children) =>
{
	return {
		class: 'pin w-[32px] h-[32px] m-[16px] block cursor-pointer',
		onState: ['pinned', (val) => (PinIcon(val))],
		...props,
		children
	};
});

/**
 * This will create the main header.
 *
 * @param {object} props
 * @returns {object}
 */
export const MainHeader = (props) => (
	Div({ class: 'flex flex-row justify-between min-w-[330px]'}, [
		Logo({ src: props.src || '/images/logo.svg' }),
		//PinButton({ click: props.callBack })
	])
);