import { CircleGraph, ProgressBar } from "@base-framework/ui/atoms";
import { DocSection } from "../../../molecules/doc-section.js";
import { DocPage } from '../../doc-page.js';

/**
 * ProgressBarPage
 *
 * This will create a dialog page.
 *
 * @returns {DocPage}
 */
export const ProgressBarPage = () => (
	DocPage(
        {
            title: 'Progress Bars',
            description: 'Progress bars are a great way to show the progress of a task or process.',
        },
        [
            DocSection({
                title: 'Usage',
                description: 'Import the progress bar atom and use it in your components. The progress bar can be used to show the progress of a task or process.',
                preview: [
                    new ProgressBar({ cache: 'progress', progress: 20 })
                ],
                code: `import {  ProgressBar  } from "@base-framework/ui/atoms";

new ProgressBar({ cache: 'progress', progress: 20 })`
            }),

            DocSection({
                title: 'Circle Progress Bar',
                description: 'The progress bar can also be displayed as a circle.',
                preview: [
                    CircleGraph({ cache: 'progress', progress: 50, class: 'stroke-gray-300' })
                ],
                code: `import {  ProgressBar  } from "@base-framework/ui/atoms";

new ProgressBar({ cache: 'progress', progress: 20 })`
            })
        ]
    )
);

export default ProgressBarPage;