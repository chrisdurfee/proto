import { Div } from '@base-framework/atoms';
import { StepHeader, StepItemCard } from '../atoms/atoms.js';
import { StepContainer } from '../atoms/step-container.js';

/**
 * ModulesSection component that renders the modular development section.
 *
 * @returns {object}
 */
export const ModulesSection = () => (
	StepContainer({ variant: 'right' }, [
		Div({ class: 'flex flex-col items-start' }, [
			StepHeader({
				title: 'Modular Development',
				description: 'The app is built with a modular architecture for easy extensibility and team collaboration.'
			}),

			StepItemCard({
				title: 'Module Structure',
				content: 'Each module is self-contained in the src/modules directory with its own: components, pages, routes, and business logic.'
			}),

			StepItemCard({
				title: 'Extensibility',
				content: 'Easily extend the app by adding new modules that integrate seamlessly with existing ones.'
			})
		])
	])
);