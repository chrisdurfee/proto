import { I, Li, Span } from "@base-framework/atoms";
import { NavLink } from "@base-framework/base";

/**
 * This will get the max height.
 *
 * @param {object} props
 * @returns {string}
 */
const getMaxHeight = (props) =>
{
	if (props.maxHeight)
	{
		return props.maxHeight;
	}

	return 'max-h-[80px]';
};

/**
 * This will create a mobile link.
 *
 * @param {object} props
 * @returns {object}
 */
export const MobileLink = (props) => (
	Li({ class: `mobile-link flex flex-auto items-center justify-center ${getMaxHeight(props)}`, click: props.click }, [
		new NavLink({
			class: 'p-[4px] flex flex-col items-center justify-center rounded-md md:hover:bg-accent transition-colors duration-200',
			href: props.href,
			exact: props.exact || false,
		},
		[
			I({ class: 'flex items-center justify-center py-[2px] px-3 rounded-md', html: props.icon }),
			Span({ class: 'text-[10px]' }, props.label)
		])
	])
);