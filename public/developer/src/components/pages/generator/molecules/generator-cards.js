import { Div } from "@base-framework/atoms";
import { Icons } from "@base-framework/ui/icons";
import { ResourceCard } from "../atoms/resource-card.js";
import { GeneratorModal } from "../modals/generator-modal.js";

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
			click: () => GeneratorModal({
                resourceType: 'Full Resource'
            }),
			icon: Icons.document.duplicate
		}),
		ResourceCard({
			title: 'API',
			click: () => GeneratorModal({
                resourceType: 'API'
            }),
			icon: Icons.document.duplicate
		}),
		ResourceCard({
			title: 'Controller',
			click: () => GeneratorModal({
                resourceType: 'Controller'
            }),
			icon: Icons.document.duplicate
		}),
		ResourceCard({
			title: 'Model',
			click: () => GeneratorModal({
                resourceType: 'Model'
            }),
			icon: Icons.document.duplicate
		}),
		ResourceCard({
			title: 'Storage',
			click: () => GeneratorModal({
                resourceType: 'Storage'
            }),
			icon: Icons.document.duplicate
		}),
		ResourceCard({
			title: 'Policy',
			click: () => GeneratorModal({
                resourceType: 'Policy'
            }),
			icon: Icons.document.duplicate
		}),
		ResourceCard({
			title: 'Table',
			click: () => GeneratorModal({
                resourceType: 'Table'
            }),
			icon: Icons.document.duplicate
		}),
		ResourceCard({
			title: 'Migration',
			click: () => GeneratorModal({
                resourceType: 'Migration'
            }),
			icon: Icons.document.duplicate
		}),
		ResourceCard({
			title: 'Unit Test',
			click: () => GeneratorModal({
                resourceType: 'Unit Test'
            }),
			icon: Icons.document.duplicate
		})
	])
);

export default GeneratorCards;