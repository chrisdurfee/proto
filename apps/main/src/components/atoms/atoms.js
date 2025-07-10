import { A, Br, Div, Fieldset, Form, H1, H2, Header, Input, Label, Legend, Li, Nav, P, Span, Strong, Textarea, Ul } from '@base-framework/atoms';
import { Atom } from '@base-framework/base';
export * from "@base-framework/ui/atoms";

/**
 * This will export the base atoms.
 */
export {
	A, Br, Div, Fieldset, Form, H1,
	H2, Header, Input,
	Label, Legend, Li,
	Nav, P, Span, Strong, Textarea, Ul
};

/**
 * This will create a video object.
 *
 * @param {object} props
 * @param {array} children
 * @returns {object}
 */
export const Video = Atom((props, children) =>
{
	return {
		tag: 'video',
		...props,
		innerHTML: '<source src="' + props.src + '"></source>',
		autoplay: true,
		muted: true,
		loop: true,
		children
	};
});

export const GridPanel = Atom((props, children) =>
{
	let span = (props.span)? 'span-' + props.span : '';
	props.class = 'panel rounded-xl border bg-card text-card-foreground shadow ' + span;

	return Div(props, children);
});