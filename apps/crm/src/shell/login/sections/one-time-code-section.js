import { Div, Form, H1, Header, P, Section } from '@base-framework/atoms';
import { Atom } from '@base-framework/base';
import { Button, Input } from "@base-framework/ui/atoms";

/**
 * OneTimeCodeHeader
 *
 * Renders the header for the one-time code verification step.
 *
 * @param {object} props - The properties for the header.
 * @param {string} props.title - The title text.
 * @param {string} props.description - The description text.
 * @returns {object} A virtual DOM element representing the header.
 */
const OneTimeCodeHeader = Atom(({ title, description }) => (
	Header({ class: 'flex flex-col space-y-1.5 p-6' }, [
		H1({ class: 'scroll-m-20 text-3xl font-bold tracking-tight' }, title),
		description && P({ class: 'text-base text-muted-foreground py-2 max-w-[700px]' }, description)
	])
));

/**
 * OneTimeCodeForm
 *
 * Renders the form for entering the one-time code.
 *
 * @returns {object} A virtual DOM element representing the one-time code form.
 */
const OneTimeCodeForm = () => (
	Form({
		class: 'flex flex-col p-6 pt-0',
		submit: (e, parent) =>
		{
			e.preventDefault();
			// Handle submission logic (e.g., verify the one-time code)
			console.log('One-time code submitted');
		},
		role: 'form'
	}, [
		Div({ class: 'grid gap-4' }, [
			Input({
				type: 'text',
				placeholder: 'Enter your code',
				required: true,
				'aria-required': true
			}),
			Button({ type: 'submit' }, 'Verify Code')
		])
	])
);

/**
 * OneTimeCodeSection
 *
 * Renders the one-time code step for the login process.
 *
 * @returns {object} A virtual DOM element representing the one-time code section.
 */
export const OneTimeCodeSection = () => (
	Section({ class: 'flex flex-auto flex-col justify-center items-center' }, [
		Div({
			class: 'rounded-xl sm:border sm:shadow-lg bg-card text-card-foreground shadow w-full mx-auto max-w-sm'
		}, [
			OneTimeCodeHeader({
				title: 'One-Time Code',
				description: 'Please enter the one-time code sent to you.'
			}),
			OneTimeCodeForm()
		])
	])
);

export default OneTimeCodeSection;