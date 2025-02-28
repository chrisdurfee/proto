import { Div, H2 } from "@base-framework/atoms";
import { Card, Icon } from "@base-framework/ui/atoms";

/**
 * ResourceCard
 *
 * This will create a resource button.
 *
 * @param {object} props - Card properties
 * @param {string} props.title - The title of the card
 * @param {function} props.click - Function to handle click events
 * @param {object} props.icon - Icon to display on the card
 * @returns {object}
 */
export const ResourceCard = ({ title, click, icon }) =>
(
	Card({ class: 'flex flex-auto flex-col p-4 min-w-[280px] hover:bg-muted/50 transition-colors', margin: 'm-0' }, [
		Div({ class: 'flex flex-col justify-center items-center my-4' }, [
			Icon({ size: 'md' }, icon),
			H2({ class: 'text-base text-muted-foreground mt-4' }, title),
		])
	])
);

export default ResourceCard;