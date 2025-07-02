import { Div, H2, Header, P } from "@base-framework/atoms";
import { Atom } from "@base-framework/base";

/**
 * ProfileSection
 *
 * Generic section with a title and description, used for various profile sections.
 * @param {object} props
 * @param {string} props.title - Section title.
 * @param {string} props.description - Section description.
 * @param {Array} children - Child components to render within the section.
 * @returns {object}
 */
export const ProfileSection = Atom((props, children) => (
	Div({ class: "space-y-6" }, [
		Header({ class: "flex flex-col space-y-2" }, [
			H2({ class: "text-xl font-semibold" }, props.title),
			props.description && P({ class: "text-sm text-muted-foreground" }, props.description)
		]),
		...children
	])
));