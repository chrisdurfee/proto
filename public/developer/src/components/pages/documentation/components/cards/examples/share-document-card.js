import { Div, Input, Span } from "@base-framework/atoms";
import { Button, Card, Select } from "@base-framework/ui/atoms";
import { Icons } from "@base-framework/ui/icons";
import { Avatar } from "@base-framework/ui/molecules";
import { CardHeader } from "./card-atoms.js";

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
 * PersonWithAccess Component
 *
 * Renders a person with access information, including avatar, name, email, and access level dropdown.
 *
 * @param {object} props - Person's information.
 * @returns {object} - Access level row for each person.
 */
const PersonWithAccess = ({ name, email, access, image }) => (
    Div({ class: "flex items-center justify-between gap-4 py-2" }, [
        // Avatar and Person Info
        Div({ class: "flex items-center gap-3" }, [
            Avatar({ src: image, alt: name, fallbackText: name.split(" ").map(n => n[0]).join("") }),
            Div({}, [
                Span({ class: "font-medium text-foreground" }, name),
                Span({ class: "block text-sm text-muted-foreground" }, email),
            ])
        ]),

        // Access Level Selector
        Select({
            class: "border rounded-md bg-background text-foreground px-3 py-1",
            options: [
                { value: "edit", label: "Can edit" },
                { value: "view", label: "Can view" },
                { value: "comment", label: "Can comment" }
            ],
            defaultValue: access,
            change: (e) => console.log(`Access level changed for ${name}: ${e.target.value}`)
        })
    ])
);

/**
 * PeopleWithAccessList Component
 *
 * Renders a list of people with access.
 *
 * @param {object} props
 * @returns {object} - List of people with access.
 */
const PeopleWithAccessList = ({ people }) => (
    Div({ class: "space-y-3" },
        people.map(person => PersonWithAccess(person))
    )
);

/**
 * ShareDocumentCard Component
 *
 * This component renders the share document card with a header, shareable link, and list of people with access.
 *
 * @returns {object} - The complete share document card.
 */
export const ShareDocumentCard = ({ link, people }) => (
    Card({ class: "w-full max-w-md mx-auto p-6 bg-card space-y-6" }, [
        CardHeader({ title: "Share this document", description: "Anyone with the link can view this document." }),

        // Shareable Link Section
        ShareLink({ link }),

        // Divider
        Div({ class: "border-t my-4" }),

        // People with Access Section Header
        Div({ class: "font-medium text-foreground" }, "People with access"),

        // List of People with Access
        PeopleWithAccessList({ people })
    ])
);

export default ShareDocumentCard;