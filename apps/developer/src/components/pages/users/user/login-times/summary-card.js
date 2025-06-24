import { Div, H2, P } from "@base-framework/atoms";
import { Card, Icon } from "@base-framework/ui/atoms";

/**
 * SummaryCard
 *
 * A card specifically designed for displaying summary statistics.
 *
 * @param {object} props - Card properties
 * @param {string} props.title - The title of the card
 * @param {string|number} props.value - The main value to display
 * @param {string} props.change - Text to display for percentage or value change
 * @param {object} props.icon - Icon to display on the card
 * @returns {object}
 */
export const SummaryCard = ({ title, value, change, icon }) => (
	Card({ class: 'flex flex-col w-72 max-w-sm hover:bg-muted/50 transition-colors', margin: 'm-0' }, [
		Div({ class: 'flex justify-between items-center mb-2' }, [
			H2({ class: 'text-base font-medium text-muted-foreground' }, title),
			Icon(icon)
		]),
		Div({ class: 'flex flex-col' }, [
			P({ class: 'text-2xl font-bold leading-tight' }, value),
			P({ class: 'text-xs text-muted-foreground mt-1' }, change)
		])
	])
);

export default SummaryCard;