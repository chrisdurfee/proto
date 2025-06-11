import { OnState } from "@base-framework/atoms";
import { PopOverNavigation } from "./popover-navigation.js";

/**
 * This will create a popover container.
 *
 * @param {object} props
 * @returns {object}
 */
export const PopOverContainer = ({ options }) => (
	OnState('open', (isOpen, ele, parent) => (!isOpen)
		? null
		: new PopOverNavigation({
			cache: 'popover',
			parent: parent,
			options
		})
	)
);