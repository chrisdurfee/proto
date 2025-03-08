import { P } from "@base-framework/atoms";
import { Button } from "@base-framework/ui/atoms";
import { Icons } from "@base-framework/ui/icons";
import { Notification } from "@base-framework/ui/molecules";
import { DocSection } from "../../../molecules/doc-section.js";
import { DocPage } from '../../doc-page.js';

/**
 * NotificationPage
 *
 * This will create a notification page.
 *
 * @returns {DocPage}
 */
export const NotificationPage = () => (
    DocPage(
        {
            title: 'Notifications',
            description: 'Displays a notification message to the user.'
        },
        [
            // Default Notification
            DocSection({
                title: 'Default Notification',
                description: 'This is a standard notification without any specific type.',
                preview: [
                    new Notification({
                        title: 'Default Notification',
                        description: 'This is a default notification.',
                        icon: Icons.information,
                        duration: 'infinite',
                    })
                ],
                code: `app.notify({
    title: 'Default Notification',
    description: 'This is a default notification.',
    icon: Icons.info,
    duration: 'infinite'
})`
            }),

            P({ class: 'text-muted-foreground' }, 'The notification can either be a link with an "href" attribute or a button with an "onClick" event.'),

            // Info Notification
            DocSection({
                title: 'Info Notification',
                description: 'An informational notification to inform users about general information.',
                preview: [
                    new Notification({
                        title: 'Heads up!',
                        description: 'You can add components to your app using the CLI.',
                        icon: Icons.information,
                        type: 'info',
                        duration: 'infinite',
                    })
                ],
                code: `app.notify({
    title: 'Heads up!',
    description: 'You can add components to your app using the CLI.',
    icon: Icons.information,
    type: 'info',
    duration: 'infinite'
})`
            }),

            // Warning Notification
            DocSection({
                title: 'Warning Notification',
                description: 'A warning notification to caution the user about potential issues.',
                preview: [
                    new Notification({
                        title: 'Warning!',
                        description: 'Please check your input before proceeding.',
                        icon: Icons.warning,
                        type: 'warning',
                        duration: 'infinite',
                    })
                ],
                code: `app.notify({
    title: 'Warning!',
    description: 'Please check your input before proceeding.',
    icon: Icons.warning,
    type: 'warning',
    duration: 5000
})`
            }),

            // Destructive Notification
            DocSection({
                title: 'Destructive Notification',
                description: 'A destructive notification to alert the user of a serious issue or error.',
                preview: [
                    new Notification({
                        title: 'Error!',
                        description: 'Something went wrong. Please try again later.',
                        icon: Icons.shield,
                        type: 'destructive',
                        duration: 'infinite',
                    })
                ],
                code: `app.notify({
    title: 'Error!',
    description: 'Something went wrong. Please try again later.',
    icon: Icons.warning,
    type: 'destructive',
    duration: 'infinite'
})`
            }),

            // Success Notification
            DocSection({
                title: 'Success Notification',
                description: 'A success notification to confirm a successful action or process.',
                preview: [
                    new Notification({
                        title: 'Success!',
                        description: 'The action was successful.',
                        icon: Icons.check,
                        type: 'success',
                        duration: 'infinite',
                    })
                ],
                code: `app.notify({
    title: 'Success!',
    description: 'The action was successful.',
    icon: Icons.check,
    type: 'success',
    duration: 'infinite'
})`
            }),

            // Notification with Primary and Secondary Buttons
            DocSection({
                title: 'Notification with Actions',
                description: 'This notification includes primary and secondary buttons for user actions.',
                preview: [
                    new Notification({
                        title: 'Action Required',
                        description: 'Would you like to proceed with this action?',
                        icon: Icons.information,
                        type: 'info',
                        primary: 'Proceed',
                        primaryAction: () => console.log(1),
                        secondary: 'Cancel',
                        secondaryAction: () => console.log(1),
                        duration: 'infinite'
                    })
                ],
                code: `app.notify({
    title: 'Action Required',
    description: 'Would you like to proceed with this action?',
    icon: Icons.question,
    type: 'info',
    primary: 'Proceed',
    primaryAction: () => alert('Primary action clicked!'),
    secondary: 'Cancel',
    secondaryAction: () => alert('Secondary action clicked!'),
    duration: 'infinite' // Notification will remain until user dismisses
})`
            }),

            P({ class: 'text-muted-foreground' }, 'Notifications will stack if multiple are triggered at the same time. The notification will remain visible until the specified duration has passed. If a notification duration is set to \'infinite\', it must be manually dismissed by the user.'),

            DocSection({
                title: 'Usage',
                description: 'Call \'notify\' on the global notification container to display notifications.',
                preview: [
                    Button({
                        click: () =>
                        {
                            // @ts-ignore
                            app.notify({
                                title: 'Notification Title',
                                description: 'This is a notification.',
                                icon: Icons.information
                            });
                        }
                    }, 'Show Notification')
                ],
                code: `app.notify({
    title: 'Notification Title',
    description: 'This is a notification.',
    icon: Icons.warning,
    type: 'warning',
    duration: 5000 // in milliseconds, leave blank for the default 4 seconds, or use 'infinite' to keep it until dismissed
})`
            })
        ]
    )
);

export default NotificationPage;
