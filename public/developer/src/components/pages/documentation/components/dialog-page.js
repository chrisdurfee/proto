import { Div, P } from "@base-framework/atoms";
import { Atom } from "@base-framework/base";
import { Button, Input } from "@base-framework/ui/atoms";
import { Icons } from "@base-framework/ui/icons";
import { Confirmation, Dialog } from "@base-framework/ui/molecules";
import { DocSection } from "../../../molecules/doc-section.js";
import { DocPage } from '../../doc-page.js';

/**
 * This will create a dialog button.
 *
 * @param {object} props
 * @returns {object}
 */
const DialogButton = Atom((props, children) => Button({
	text: 'Open',
	class: `m-1`,
	click: () => new Dialog(props, children).open()
}));

/**
 * This will create a confirmation button.
 *
 * @param {object} props
 * @returns {object}
 */
const ConfirmationButton = (props) => Button({
	text: 'Open',
	class: `m-1`,
	click: () => new Confirmation(props).open()
});

/**
 * This will create a type dialog button.
 *
 * @param {object} props
 * @returns {object}
 */
const TypeDialogButton = Atom((props, children) => Button({
    text: 'Open',
    class: `m-1`,
    click: () => new Confirmation({
        icon: Icons.signOut,
        type: 'destructive',
        title: 'Are you absoultely sure?',
        description: 'This will sign you out of the application.',
        confirmTextLabel: 'Sign Out',
        confirmed: () => console.log('Confirmed!')
    }).open()
}));

/**
 * ShareLink Component
 *
 * Renders the shareable link with a copy button.
 *
 * @param {object} props - share link information.
 * @returns {object} - Share link element.
 */
const ShareLink = ({ link }) => (
    Div({ class: "flex items-center gap-4 py-2" }, [
        Input({
            type: "text",
            value: link,
            readOnly: true,
            class: "border p-2 rounded-md w-full bg-background text-foreground"
        }),
        Button({
            variant: 'secondary',
            class: "px-4 py-2",
            click: () => {
                navigator.clipboard.writeText(link);

                // @ts-ignore
                app.notify({
                    title: "Link copied",
                    description: "The link has been copied to your clipboard.",
                    icon: Icons.clipboard.checked
                })
            }
        }, "Copy Link")
    ])
);

/**
 * This will create a confirmation button.
 *
 * @param {object} props
 * @returns {object}
 */
const ShareButton = (props) => Button({
	text: 'Open',
	class: `m-1`,
	click: () => new Dialog(props, [
        ShareLink({ link: 'https://example.com' })
    ]).open()
});

/**
 * DialogPage
 *
 * This will create a dialog page.
 *
 * @returns {DocPage}
 */
export const DialogPage = () => (
	DocPage(
        {
            title: 'Dialogs',
            description: 'Displays a dialog or a component that looks like a dialog. Dialogs can be opened and closed. They will be closed if the user clicks outside of the dialog or if the user clicks the close button.',
        },
        [
            DocSection({
                title: 'Usage',
                description: 'Import the dialog atom and use it in your components.',
                preview: [
                    DialogButton({
                        title: 'Title',
                        description: 'this is the description.',
                        hideFooter: false
                    }, [

                    ])
                ],
                code: `import {  Dialog  } from "@base-framework/ui/molecules";

new Dialog({
    title: '',
    description: '',
    hideFooter: false
}, [

]).open()`
            }),

            DocSection({
                title: 'Confirmation',
                description: 'Displays a confirmation dialog.',
                preview: [
                    ConfirmationButton({
                        title: 'Are you absoultely sure?',
                        description: 'This action cannot be undone. This will permanently delete your account and remove your data from our servers.',
                        confirmTextLabel: 'Save',
                        confirmed: () => console.log('Confirmed!')
                    })
                ],
                code: `import {  Confirmation  } from "@base-framework/ui/molecules";

new Confirmation({
    title: 'Are you absoultely sure?',
    description: 'This action cannot be undone. This will permanently delete your account and remove your data from our servers.',
    confirmTextLabel: 'Save',
    confirmed: () => console.log('Confirmed!')
}).open()`
            }),

            DocSection({
                title: 'Share Link',
                description: 'Displays a dialog with a shareable link.',
                preview: [
                    ShareButton({
                        title: 'Share Link',
                        description: 'Share this link with others.',
                    })
                ],
                code: `import {  Confirmation  } from "@base-framework/ui/molecules";

new Confirmation({
    title: 'Are you absoultely sure?',
    description: 'This action cannot be undone. This will permanently delete your account and remove your data from our servers.',
    confirmTextLabel: 'Save',
    confirmed: () => console.log('Confirmed!')
}).open()`
            }),

            // the dialog types
            DocSection({
                title: 'Types',
                description: 'There are different types of dialogs.',
                preview: [
                    TypeDialogButton()
                ],
                code: `import {  Dialog  } from "@base-framework/ui/molecules";
import {  Confirmation  } from "@base-framework/ui/molecules";
import {  Icons  } from "@base-framework/ui/icons";

new Confirmation({
    icon: Icons.signOut,
    type: 'destructive',
    title: 'Are you absoultely sure?',
    description: 'This will sign you out of the application.',
    confirmTextLabel: 'Sign Out',
    confirmed: () => console.log('Confirmed!')
}).open()
`
            }),

            P('The types are `destructive`, `warning`, `info`, `success`, and `default`. Dialogs can also have an icon in the header. The icon can be any icon from the icon library.'),
        ]
    )
);

export default DialogPage;