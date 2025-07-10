import { Div, Form, H2, Header, P, Section } from '@base-framework/atoms';
import { Atom } from '@base-framework/base';
import { Icon } from "@base-framework/ui/atoms";
import { Icons } from '@base-framework/ui/icons';

/**
 * @function VerifyingMessage
 * @description
 *  Displays a final success message with a button to go to the app.
 *
 * @returns {object} A Div component containing the verifying message.
 */
export const VerifyingMessage = Atom(() =>
(
	Div({ class: 'w-full max-w-sm bg-card text-card-foreground shadow rounded-xl sm:border sm:shadow-lg p-6' }, [
		Form({ class: 'flex flex-auto flex-col' }, [
			Div({ class: 'flex flex-auto flex-col space-y-4' }, [
				Div({ class: 'flex flex-auto items-center justify-center' }, [
					Div({ class: 'mb-6 text-primary' }, [
						Icon({ class: 'animate-spin block' }, Icons.loading)
					])
				]),
				Header({ class: 'py-4 text-center' }, [
					H2({ class: 'text-xl font-bold' }, 'Verifying...'),
					P('Your unsubscription request is being verified.'),
				])
			])
		])
	])
));

/**
 * @function VerifyingSection
 * @description
 *  A page section that displays the verifying message.
 *
 * @returns {object} A Section component containing the verifying message UI.
 */
export const VerifyingSection = () =>
(
	Section({ class: 'flex flex-auto flex-col justify-center items-center' }, [
		VerifyingMessage()
	])
);