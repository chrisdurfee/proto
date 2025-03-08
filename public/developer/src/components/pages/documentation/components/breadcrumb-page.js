import { Breadcrumb, DotsIndicator } from "@base-framework/ui/molecules";
import { DocSection } from "../../../molecules/doc-section.js";
import { DocPage } from '../../doc-page.js';

const items = [
    { href: '/', label: 'Home' },
    { href: '/components', label: 'Components' },
    { label: 'Breadcrumb' } // Last item, no href needed
];

/**
 * BreadcrumbPage
 *
 * This will create a breadcrumb page.
 *
 * @returns {DocPage}
 */
export const BreadcrumbPage = () => (
	DocPage(
        {
            title: 'Breadcrumbs',
            description: 'Breadcrumbs are a navigation aid that helps users understand their location within a website or application.',
        },
        [
            DocSection({
                title: 'Usage',
                description: 'To use the Breadcrumb component, import it and provide an array of items. Each item can have a label and an optional href.',
                preview: [
                    new Breadcrumb( { items } ),
                ],
                code: `import {  Breadcrumb  } from "@base-framework/ui/molecules";

new Breadcrumb({ items: [
    { href: '/', label: 'Home' },
    { href: '/components', label: 'Components' },
    { label: 'Breadcrumb' } // Last item, no href needed
] })`
            }),

            DocSection({
                title: 'Dots Indicator',
                description: 'The Dots Indicator component is used to display a row of circular dots, one of which is "active." The dots can be set to a specific size and count. The sizes are xs, sm, md, lg, xl, 2xl, and 3xl.',
                preview: [
                    new DotsIndicator({
                        count: 6,
                        size: 'sm',
                        gap: 'gap-2',
                        onClick: (index) => console.log('Clicked dot', index)
                    }),
                ],
                code: `import DotsIndicator from "@base-framework/ui/molecules";

new DotsIndicator({
    count: 6,
    size: 'sm',
    gap: 'gap-2',
    onClick: (index) => console.log('Clicked dot', index)
})`
            })
        ]
    )
);

export default BreadcrumbPage;