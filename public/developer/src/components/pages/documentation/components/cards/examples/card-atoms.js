import { H2, Header, P } from "@base-framework/atoms";

/**
 * This will create the card header.
 *
 * @param {object} props
 * @returns {object}
 */
export const CardHeader = ({ title, description }) => (
    Header({ class: 'text-left' }, [
        H2({ class: "font-semibold tracking-tight text-2xl" }, title),
        P({ class: "text-sm text-muted-foreground" }, description)
    ])
);