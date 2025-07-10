import { StepHeader, StepItemCard } from '../atoms/atoms.js';
import { StepContainer } from '../atoms/step-container.js';

/**
 * ThemingSection component that renders the theme support section.
 *
 * @returns {object}
 */
export const ThemingSection = () => (
    StepContainer([
        StepHeader({
            title: 'Theme Support',
            description: 'Customizable themes for a personalized experience.'
        }),

        StepItemCard({
            title: 'Light & Dark Themes',
            content: 'The base app supports light and dark themes with customizable theme variables.'
        }),

        StepItemCard({
            title: 'Mobile First Design',
            content: 'The app is designed with a mobile-first approach for a responsive experience.'
        })
    ])
);