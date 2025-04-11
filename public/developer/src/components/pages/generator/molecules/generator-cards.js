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
	Div({ class: 'grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4' }, [
		ResourceCard({
			title: 'Full Resource',
			click: () => GeneratorModal({
				resourceType: 'Full Resource'
			}),
			description: 'Create a full resource with all the necessary files.',
			icon: Icons.document.duplicate
		}),
		ResourceCard({
			title: 'Module',
			click: () => GeneratorModal({
				resourceType: 'Module'
			}),
			description: 'Create a new module directory.',
			icon: Icons.document.duplicate
		}),
		ResourceCard({
			title: 'Gateway',
			click: () => GeneratorModal({
				resourceType: 'Gateway'
			}),
			description: 'Create a module gateway.',
			icon: Icons.document.duplicate
		}),
		ResourceCard({
			title: 'API',
			click: () => GeneratorModal({
				resourceType: 'API'
			}),
			description: 'Create an API.',
			icon: Icons.document.duplicate
		}),
		ResourceCard({
			title: 'Controller',
			click: () => GeneratorModal({
				resourceType: 'Controller'
			}),
			description: 'Create a controller.',
			icon: Icons.document.duplicate
		}),
		ResourceCard({
			title: 'Model',
			click: () => GeneratorModal({
				resourceType: 'Model'
			}),
			description: 'Create a model.',
			icon: Icons.document.duplicate
		}),
		ResourceCard({
			title: 'Storage',
			click: () => GeneratorModal({
				resourceType: 'Storage'
			}),
			description: 'Create a storage.',
			icon: Icons.document.duplicate
		}),
		ResourceCard({
			title: 'Policy',
			click: () => GeneratorModal({
				resourceType: 'Policy'
			}),
			description: 'Create a policy.',
			icon: Icons.document.duplicate
		}),
		ResourceCard({
			title: 'Table',
			click: () => GeneratorModal({
				resourceType: 'Table'
			}),
			description: 'Create a database table.',
			icon: Icons.document.duplicate
		}),
		ResourceCard({
			title: 'Migration',
			click: () => GeneratorModal({
				resourceType: 'Migration'
			}),
			description: 'Create a new migration.',
			icon: Icons.document.duplicate
		}),
		ResourceCard({
			title: 'Unit Test',
			click: () => GeneratorModal({
				resourceType: 'Unit Test'
			}),
			description: 'Create a test.',
			icon: Icons.document.duplicate
		})
	])
);

export default GeneratorCards;