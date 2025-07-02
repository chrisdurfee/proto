import { Div, H1, Header, P, Section } from '@base-framework/atoms';
import { Atom } from '@base-framework/base';
import { Button } from "@base-framework/ui/atoms";
import { STEPS } from '../steps.js';

/**
 * MultiFactorMethodHeader
 *
 * Renders the header for the multi-factor authentication method selection step.
 *
 * @param {object} props - The properties for the header.
 * @param {string} props.title - The title text.
 * @param {string} props.description - The description text.
 * @returns {object} A virtual DOM element representing the header.
 */
const MultiFactorMethodHeader = Atom(({ title, description }) => (
	Header({ class: 'flex flex-col space-y-1.5 p-6' }, [
		H1({ class: 'scroll-m-20 text-3xl font-bold tracking-tight' }, title),
		description && P({ class: 'text-base text-muted-foreground py-2 max-w-[700px]' }, description)
	])
));

/**
 * MultiFactorMethodForm
 *
 * Renders the selection form for choosing a multi-factor authentication method.
 *
 * @returns {object} A virtual DOM element representing the multi-factor method selection form.
 */
const MultiFactorMethodForm = () => (
	Div({ class: 'grid gap-4 p-6' }, [
		Button({
			variant: 'outline',
			click: (e, parent) => {
				// Save the selected method in parent's state and navigate to the next multi-factor auth step
				parent.state.selectedMfaMethod = 'email';
				parent.showStep(STEPS.ONE_TIME_CODE); // navigate to the one-time code step
			}
		}, 'Email'),
		Button({
			variant: 'outline',
			click: (e, parent) => {
				parent.state.selectedMfaMethod = 'text';
				parent.showStep(STEPS.ONE_TIME_CODE); // navigate to the one-time code step
			}
		}, 'Text')
	])
);

/**
 * MultiFactorMethodSection
 *
 * Renders the multi-factor authentication method selection step for the login process.
 *
 * @returns {object} A virtual DOM element representing the multi-factor method section.
 */
export const MultiFactorMethodSection = () => (
	Section({ class: 'flex flex-auto flex-col justify-center items-center' }, [
		Div({
			class: 'rounded-xl sm:border sm:shadow-lg bg-card text-card-foreground shadow w-full mx-auto max-w-sm',
			addState: () => ({ selectedMfaMethod: '' })
		}, [
			MultiFactorMethodHeader({
				title: 'Multi-Factor Authentication',
				description: 'Select your preferred method for multi-factor authentication.'
			}),
			MultiFactorMethodForm()
		])
	])
);

export default MultiFactorMethodSection;