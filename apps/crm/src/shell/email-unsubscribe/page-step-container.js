import { Div, OnState } from '@base-framework/atoms';
import { SuccessSection } from './sections/success-section copy.js';
import { VerifyingSection } from './sections/verifying-section copy.js';
import { STEPS } from './steps.js';

/**
 * Renders the correct page section based on current step.
 *
 * @returns {object} A Div that conditionally renders each step's section.
 */
export const PageStepContainer = () =>
(
    Div({ class: 'flex flex-auto flex-col' }, [
        OnState('step', (step) =>
        {
            switch (step)
            {
                case STEPS.SUCCESS:
                    return SuccessSection();
                case STEPS.ERROR:
                    return VerifyingSection();
                default:
                    return VerifyingSection();
            }
        })
    ])
);