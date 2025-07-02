import { Div, H1, P } from '@base-framework/atoms';
import { StepContainer } from '../atoms/step-container';

/**
 * This will create a welcome section for the onboarding page.
 *
 * @returns {object} A Section component containing the two panes.
 */
export const WelcomeSection = () => (
	StepContainer([
		Div({ class: 'flex flex-col items-start' }, [
			H1({ class: 'text-2xl font-bold mb-4' }, 'App Shell Basics'),
			P({ class: 'text-base text-muted-foreground mb-4' },
				'Building apps should be fast, easy, and fun. With App Shell, you can create a new project in minutes.'
			),
			P({ class: 'text-base text-muted-foreground mb-4' },
				'In this guide, we will walk you through the necessary steps to get started.'
			)
		])
	])
);