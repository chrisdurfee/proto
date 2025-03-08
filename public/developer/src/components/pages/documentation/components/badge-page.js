import { Div } from "@base-framework/atoms";
import { Badge } from "@base-framework/ui/atoms";
import { DocSection } from "../../../molecules/doc-section.js";
import { DocPage } from '../../doc-page.js';

/**
 * BadgePage
 *
 * This will create a badge page.
 *
 * @returns {DocPage}
 */
export const BadgePage = () => (
	DocPage(
        {
            title: 'Badges',
            description: 'Badges can be used to display a status or a count.'
        },
        [
            DocSection({
                title: 'Badge',
                description: 'This is a badge.',
                preview: [
                    Div({ class: 'flex gap-2 flex-wrap' }, [
                        Badge({ type: 'gray' }, 'Text'),
                        Badge({ type: 'red' }, 'Text'),
                        Badge({ type: 'yellow' }, 'Text'),
                        Badge({ type: 'green' }, 'Text'),
                        Badge({ type: 'blue' }, 'Text'),
                        Badge({ type: 'indigo' }, 'Text'),
                        Badge({ type: 'purple' }, 'Text'),
                        Badge({ type: 'pink' }, 'Text'),
                        Badge({ type: 'primary' }, 'Text'),
                        Badge({ type: 'secondary' }, 'Text'),
                        Badge({ type: 'destructive' }, 'Text'),
                        Badge({ type: 'warning' }, 'Text'),
                        Badge({ type: 'outline' }, 'Text'),
                        Badge({ type: 'ghost' }, 'Text'),
                        Badge({ type: 'link' }, 'Text')
                    ])
                ],
                code: `
import {  Button  } from "@base-framework/ui/atoms";
import { Atom } from '@base-framework/base';

export const PrimaryBadge = Atom((props, children)) => (
    Badge({ ...props, type: 'gray' }, children)
);`
            })
        ]
    )
);

export default BadgePage;