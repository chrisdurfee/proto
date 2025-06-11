import { Div } from "@base-framework/atoms";
import { Atom } from "@base-framework/base";
import { ButtonTab } from "@base-framework/ui/organisms";
import { CodeCard, PreviewCard } from "./cards.js";

/**
 * This will create a tab content.
 *
 * @param {object} props
 * @param {object} children
 * @returns {object}
 */
const TabContent = Atom((props, children) => (
    Div({ class: 'py-4' }, children)
));

/**
 * This will create a preview tab.
 *
 * @param {object} props
 * @returns {object}
 */
export const PreviewTab = (props) => (
    new ButtonTab({
        class: 'mt-4 max-w-[400px]',
        options: [
            {
                label: 'Preview',
                value: 'preview',
                layout: TabContent([
                    PreviewCard(props.preview)
                ])
            },
            {
                label: 'Code',
                value: 'code',
                layout: TabContent([
                    CodeCard(props.code)
                ])
            }
        ]
    })
);