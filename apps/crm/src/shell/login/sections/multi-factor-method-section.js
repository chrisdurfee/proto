import { Div, H1, Header, P, Section } from '@base-framework/atoms';
import { Atom } from '@base-framework/base';
import { Button } from "@base-framework/ui/atoms";
import { AuthModel } from '../../../../../common/models/auth-model.js';
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
 * Requests a verification code for the selected multi-factor authentication option.
 *
 * @param {object} option - The selected multi-factor authentication option.
 */
const requestCode = (option) =>
{
	const model = new AuthModel({
		type: option.type
	});

	model.xhr.getAuthCode();
};

/**
 * MultiFactorMethodForm
 *
 * Renders the selection form for choosing a multi-factor authentication method.
 *
 * @returns {object} A virtual DOM element representing the multi-factor method selection form.
 */
const MultiFactorMethodForm = () => (
	Div({
		class: 'grid gap-4 p-6',
		for: ['options', (option) => (
			Button({
				variant: 'primary',
				class: 'capitalize',
				click: (e, parent) =>
				{
					parent.context.data.selectedMfaOption = option;
					requestCode(option);

					parent.showStep(STEPS.ONE_TIME_CODE);
				}
			}, `${option.type === 'sms' ? 'Text' : 'Email'}: ${option.value}`)
		)]
	})
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
			class: 'rounded-xl sm:border sm:shadow-lg bg-card text-card-foreground shadow w-full mx-auto max-w-sm'
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