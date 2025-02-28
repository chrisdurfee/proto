import { Div } from "@base-framework/atoms";
import { Icons } from "@base-framework/ui/icons";
import { ResourceCard } from "./resource-card.js";

/**
 * GeneratorCards
 *
 * A section displaying the generator resource cards in a responsive grid.
 *
 * @returns {object}
 */
export const GeneratorCards = () =>
(
	Div({ class: 'grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4' }, [
		ResourceCard({
			title: 'Full Resource',
			click: () => {},
			icon: Icons.document.duplicate
		}),
		ResourceCard({
			title: 'API',
			click: () => {},
			icon: Icons.document.duplicate
		}),
		ResourceCard({
			title: 'Controller',
			click: () => {},
			icon: Icons.document.duplicate
		}),
		ResourceCard({
			title: 'Model',
			click: () => {},
			icon: Icons.document.duplicate
		}),
		ResourceCard({
			title: 'Storage',
			click: () => {},
			icon: Icons.document.duplicate
		}),
		ResourceCard({
			title: 'Policy',
			click: () => {},
			icon: Icons.document.duplicate
		}),
		ResourceCard({
			title: 'Table',
			click: () => {},
			icon: Icons.document.duplicate
		}),
		ResourceCard({
			title: 'Migration',
			click: () => {},
			icon: Icons.document.duplicate
		}),
		ResourceCard({
			title: 'Unit Test',
			click: () => {},
			icon: Icons.document.duplicate
		})
	])
);

export default GeneratorCards;