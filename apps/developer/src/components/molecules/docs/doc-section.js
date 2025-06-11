import { H3, Header, P, Section } from "@base-framework/atoms";
import { Atom } from "@base-framework/base";
import { PreviewTab } from "./preview-tab.js";

/**
 * This will create a header for the documentation.
 *
 * @param {object} props
 * @returns {object}
 */
export const SectionHeader = Atom(({ title, description}) => (
	Header({ class: 'flex flex-col' }, [
		H3({ class: 'scroll-m-20 text-2xl font-bold tracking-tight' }, title),
		description && P({ class: 'text-base text-muted-foreground py-2 max-w-[700px]' }, description),
	])
));

/**
 * This will create a document section.
 *
 * @param {object} props
 * @param {object} children
 * @returns {object}
 */
export const DocSection = Atom((props, children) => (
    Section({ class: 'grid py-4' }, [
        SectionHeader({
            title: props.title,
            description: props.description
        }),
        PreviewTab({
            preview: props.preview,
            code: props.code
        })
    ])
));