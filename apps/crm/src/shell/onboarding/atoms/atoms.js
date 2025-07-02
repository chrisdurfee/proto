import { Button, Div, H2, Header, P, Span } from '@base-framework/atoms';
import { Atom } from '@base-framework/base';

/**
 * This will create the card header.
 *
 * @param {object} props
 * @returns {object}
 */
export const StepHeader = ({ title, description }) => (
	Header({ class: 'text-left flex flex-auto flex-col space-y-1 mb-4' }, [
		H2({ class: "font-semibold tracking-tight text-2xl" }, title),
		P({ class: "text-base text-muted-foreground" }, description)
	])
);

/**
 * This will create a form wrapper.
 *
 * @param {object} props
 * @param {array} children
 * @returns {object}
 */
export const FormWrapper = Atom((props, children) => (
	Div({ class: 'w-full mx-auto max-w-sm p-6' }, children)
));

/**
 * This will create a step item card.
 *
 * @param {object} props
 * @param {array} children
 * @returns {object}
 */
export const StepItemCard = Atom((props, children) => (
	Button({ class: 'flex flex-auto flex-col items-start gap-2 rounded-lg border p-3 text-left transition-all hover:bg-muted/50 my-2 ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus:ring-2 focus:ring-ring focus:ring-offset-2' }, [
		Div({ class: 'flex flex-auto flex-col w-full gap-2' }, [
			Div({ class: "flex w-full flex-col gap-1" }, [
				Span({ class: "font-semibold text-base text-foreground" }, props.title),
			]),
			P({ class: "text-base text-muted-foreground" }, props.content),
			Div({ class: "mt-2" }, children),
		])
	])
));