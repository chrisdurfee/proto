import { Atom } from "@base-framework/base";
import { Div, GridPanel } from "../atoms/atoms.js";

/**
 * This will create a grid container.
 *
 * @param {object} props
 * @param {array} children
 * @returns {object}
 */
export const GridContainer = Atom((props, children) =>
{
	const items = new Array(18).fill(1);
	return Div({ class: 'grid-container', map: [items, () => GridPanel()], ...props });
});