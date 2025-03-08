import { Icons } from "@base-framework/ui/icons";
import { Alert } from "@base-framework/ui/molecules";
import { DocSection } from "../../../molecules/doc-section.js";
import { DocPage } from '../../doc-page.js';

/**
 * AlertPage
 *
 * This will create an alert page.
 *
 * @returns {DocPage}
 */
export const AlertPage = () => (
    DocPage(
        {
            title: 'Alerts',
            description: 'Alerts are used to provide feedback to the user about the outcome of an action or to inform them of important information.',
        },
        [
            // Default Alert
            DocSection({
                title: 'Default Alert',
                description: 'This is a standard alert without any specific type.',
                preview: [
                    Alert({
                        title: 'Notice',
                        description: 'This is a default alert without a specific type.',
                        icon: Icons.information
                    })
                ],
                code: `import {  Alert  } from "@base-framework/ui/molecules";
import {  Icons  } from "@base-framework/ui/icons";

Alert({
    title: 'Notice',
    description: 'This is a default alert without a specific type.',
    icon: Icons.information
})`
            }),

            // Info Alert
            DocSection({
                title: 'Info Alert',
                description: 'An info alert that provides general information to the user.',
                preview: [
                    Alert({
                        title: 'Heads up!',
                        description: 'You can add components to your app using the CLI.',
                        icon: Icons.information,
                        type: 'info'
                    })
                ],
                code: `Alert({
    title: 'Heads up!',
    description: 'You can add components to your app using the CLI.',
    icon: Icons.information,
    type: 'info'
})`
            }),

            // Success Alert
            DocSection({
                title: 'Success Alert',
                description: 'A success alert to inform the user of a successful action.',
                preview: [
                    Alert({
                        title: 'Success!',
                        description: 'Your changes have been saved successfully.',
                        icon: Icons.check,
                        type: 'success'
                    })
                ],
                code: `Alert({
    title: 'Success!',
    description: 'Your changes have been saved successfully.',
    icon: Icons.check,
    type: 'success'
})`
            }),

            // Warning Alert
            DocSection({
                title: 'Warning Alert',
                description: 'A warning alert to caution the user about something important.',
                preview: [
                    Alert({
                        title: 'Warning!',
                        description: 'Please check your input before proceeding.',
                        icon: Icons.warning,
                        type: 'warning'
                    })
                ],
                code: `Alert({
    title: 'Warning!',
    description: 'Please check your input before proceeding.',
    icon: Icons.warning,
    type: 'warning'
})`
            }),

            // Destructive Alert
            DocSection({
                title: 'Destructive Alert',
                description: 'A destructive alert to inform the user of an error or serious issue.',
                preview: [
                    Alert({
                        title: 'Error!',
                        description: 'Something went wrong. Please try again later.',
                        icon: Icons.shield,
                        type: 'destructive'
                    })
                ],
                code: `Alert({
    title: 'Error!',
    description: 'Something went wrong. Please try again later.',
    icon: Icons.error,
    type: 'destructive'
})`
            })
        ]
    )
);

export default AlertPage;
