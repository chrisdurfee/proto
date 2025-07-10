import { Div } from '@base-framework/atoms';
import { StepHeader, StepItemCard } from '../atoms/atoms.js';
import { StepContainer } from '../atoms/step-container.js';

/**
 * This will create the project setup section.
 *
 * @returns {object}
 */
export const ProjectSetupSection = () =>
{
	return StepContainer({ variant: 'right' }, [
		Div({ class: 'flex flex-col items-start' }, [
			StepHeader({
				title: 'Project Setup',
				description: 'Modular, scalable, and easy to configure.'
			}),

			StepItemCard({
				title: 'Configurable Setup',
				content: 'Easily configure your project with a few simple steps to get started.'
			}),

			StepItemCard({
				title: 'Vite Support',
				content: 'Leverage Vite for a fast and efficient development experience.'
			}),

			StepItemCard({
				title: 'Installable',
				content: 'The app is installable on any device for a native-like experience using Progressive Web App (PWA) features.'
			})
		])
	]);
};