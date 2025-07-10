import { Div } from '@base-framework/atoms';
import { StepHeader, StepItemCard } from '../atoms/atoms.js';
import { StepContainer } from '../atoms/step-container.js';

/**
 * AppShellSection renders the default application shell pages.
 *
 * @returns {object}
 */
export const AppShellSection = () => (
    StepContainer([
        Div({ class: 'flex flex-col items-start' }, [
            StepHeader({
                title: 'Default Pages',
                description: 'The App Shell is set up with default pages for easy customization.'
            }),

            StepItemCard({
                title: 'Sign Up',
                content: 'The sign up page is set up to allow for multi step form submission.'
            }),

            StepItemCard({
                title: 'Onboarding',
                content: 'The onboarding process is designed to guide users through the initial setup effortlessly.'
            }),

            StepItemCard({
                title: 'Sign In & Sign Out',
                content: 'The sign in and sign out will update the app to reflect the user\'s authentication status.'
            })
        ])
    ])
);