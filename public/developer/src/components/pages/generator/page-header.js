import { Div, H1, Header } from "@base-framework/atoms";

/**
 * This will create a page header for the clients page.
 *
 * @returns {object}
 */
export const PageHeader = () => (
    Header({ class: 'flex flex-auto flex-col' }, [
        Div({ class: 'flex flex-auto items-center justify-between w-full' }, [
            H1({ class: 'text-3xl font-bold' }, 'Generator')
        ])
    ])
);