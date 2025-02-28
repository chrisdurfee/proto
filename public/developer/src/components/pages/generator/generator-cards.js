import { Div } from "@base-framework/atoms";
import { Icons } from "@base-framework/ui/icons";
import { GeneratorModal } from "./generator-modal.js";
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
			click: () => GeneratorModal(),
			icon: Icons.document.duplicate
		}),
		ResourceCard({
			title: 'API',
			click: () => GeneratorModal(),
			icon: Icons.document.duplicate
		}),
		ResourceCard({
			title: 'Controller',
			click: () => GeneratorModal(),
			icon: Icons.document.duplicate
		}),
		ResourceCard({
			title: 'Model',
			click: () => GeneratorModal(),
			icon: Icons.document.duplicate
		}),
		ResourceCard({
			title: 'Storage',
			click: () => GeneratorModal(),
			icon: Icons.document.duplicate
		}),
		ResourceCard({
			title: 'Policy',
			click: () => GeneratorModal(),
			icon: Icons.document.duplicate
		}),
		ResourceCard({
			title: 'Table',
			click: () => GeneratorModal(),
			icon: Icons.document.duplicate
		}),
		ResourceCard({
			title: 'Migration',
			click: () => GeneratorModal(),
			icon: Icons.document.duplicate
		}),
		ResourceCard({
			title: 'Unit Test',
			click: () => GeneratorModal(),
			icon: Icons.document.duplicate
		})
	])
);

export default GeneratorCards;