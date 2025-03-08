import { Div, P } from "@base-framework/atoms";
import { Button, Tooltip } from "@base-framework/ui/atoms";
import { DocSection } from "../../../molecules/doc-section.js";
import { DocPage } from '../../doc-page.js';

/**
 * TooltipPage
 *
 * This will create a tooltip page.
 *
 * @returns {DocPage}
 */
export const TooltipPage = () => (
	DocPage(
        {
            title: 'Tooltips',
            description: 'Tooltips are small popups that appear when a user hovers over an element.',
        },
        [
            DocSection({
                title: 'Usage',
                description: 'Import the tooltip atom and use it in your components.',
                preview: [
                    Div({ class: 'flex gap-2 flex-wrap' }, [
                        Tooltip({ content: 'This is a tooltip.' }, [
                            Button('Hover me')
                        ])
                    ])
                ],
                code: `import {  Button  } from "@base-framework/ui/atoms";
import {  Tooltip  } from "@base-framework/ui/atoms";

Tooltip({ content: 'This is a tooltip.' }, [
    Button('Hover me')
])`
            }),

            DocSection({
                title: 'Bottom Tooltip',
                description: 'This will show the tooltip at the bottom of the target element.',
                preview: [
                    Div({ class: 'flex gap-2 flex-wrap' }, [
                        Tooltip({ content: 'This is a tooltip.', position: 'bottom' }, [
                            Button('Hover me')
                        ])
                    ])
                ],
                code: `Tooltip({ content: 'This is a tooltip.', position: 'bottom' }, [
    Button('Hover me')
])`
            }),

            DocSection({
                title: 'Top-Right Tooltip',
                description: 'This will show the tooltip at the top-right of the target element.',
                preview: [
                    Div({ class: 'flex gap-2 flex-wrap' }, [
                        Tooltip({ content: 'This is a tooltip.', position: 'top-right' }, [
                            Button('Hover me')
                        ])
                    ])
                ],
                code: `Tooltip({ content: 'This is a tooltip.', position: 'top-right' }, [
    Button('Hover me')
])`
            }),

            DocSection({
                title: 'Top-Left Tooltip',
                description: 'This will show the tooltip at the top-left of the target element.',
                preview: [
                    Div({ class: 'flex gap-2 flex-wrap' }, [
                        Tooltip({ content: 'This is a tooltip.', position: 'top-left' }, [
                            Button('Hover me')
                        ])
                    ])
                ],
                code: `Tooltip({ content: 'This is a tooltip.', position: 'top-left' }, [
    Button('Hover me')
])`
            }),

            DocSection({
                title: 'Bottom-Right Tooltip',
                description: 'This will show the tooltip at the bottom-right of the target element.',
                preview: [
                    Div({ class: 'flex gap-2 flex-wrap' }, [
                        Tooltip({ content: 'This is a tooltip.', position: 'bottom-right' }, [
                            Button('Hover me')
                        ])
                    ])
                ],
                code: `Tooltip({ content: 'This is a tooltip.', position: 'bottom-right' }, [
    Button('Hover me')
])`
            }),

            DocSection({
                title: 'Bottom-Left Tooltip',
                description: 'This will show the tooltip at the bottom-left of the target element.',
                preview: [
                    Div({ class: 'flex gap-2 flex-wrap' }, [
                        Tooltip({ content: 'This is a tooltip.', position: 'bottom-left' }, [
                            Button('Hover me')
                        ])
                    ])
                ],
                code: `Tooltip({ content: 'This is a tooltip.', position: 'bottom-left' }, [
    Button('Hover me')
])`
            }),

            DocSection({
                title: 'Left Tooltip',
                description: 'This will show the tooltip to the left of the target element.',
                preview: [
                    Div({ class: 'flex gap-2 flex-wrap' }, [
                        Tooltip({ content: 'This is a tooltip.', position: 'left' }, [
                            Button('Hover me')
                        ])
                    ])
                ],
                code: `Tooltip({ content: 'This is a tooltip.', position: 'left' }, [
    Button('Hover me')
])`
            }),

            DocSection({
                title: 'Right Tooltip',
                description: 'This will show the tooltip to the right of the target element.',
                preview: [
                    Div({ class: 'flex gap-2 flex-wrap' }, [
                        Tooltip({ content: 'This is a tooltip.', position: 'right' }, [
                            Button('Hover me')
                        ])
                    ])
                ],
                code: `Tooltip({ content: 'This is a tooltip.', position: 'right' }, [
    Button('Hover me')
])`
            }),

            P({ class: 'text-muted-foreground text-sm mt-4' }, 'The tooltip position can be set to: top, top-right, top-left, bottom, bottom-right, bottom-left, left, right.')
        ]
    )
);

export default TooltipPage;
