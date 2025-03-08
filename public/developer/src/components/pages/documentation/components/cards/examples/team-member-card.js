import { Div, Span } from "@base-framework/atoms";
import { Card, Select } from "@base-framework/ui/atoms";
import { Avatar } from "@base-framework/ui/molecules";
import { CardHeader } from "./card-atoms.js";

/**
 * This will get the initials from the name.
 *
 * @param {string} name
 * @returns {string}
 */
const getInitials = (name) =>
{
    const names = name.split(' ');
    return names.map(n => n[0]).join('');
};

/**
 * TeamMember Component
 *
 * A single team member row with avatar, name, email, and role selection.
 *
 * @param {object} props - member information.
 * @returns {object} - Team member row element.
 */
const TeamMember = ({ name, email, role, image }) => (
    Div({ class: "flex items-center justify-between gap-4 py-2" }, [
        // Avatar and Member Info
        Div({ class: "flex items-center gap-3" }, [
            Avatar({ src: image, alt: name, fallbackText: getInitials(name) }),
            Div({}, [
                Span({ class: "font-medium text-foreground" }, name),
                Span({ class: "block text-sm text-muted-foreground" }, email),
            ])
        ]),

        // Role Selector
        Select({
            class: "border rounded-md bg-background text-foreground px-3 py-1",
            options: [
                { value: "owner", label: "Owner" },
                { value: "member", label: "Member" }
            ],
            defaultValue: role,
            change: (e) => console.log(`Role changed for ${name}: ${e.target.value}`)
        })
    ])
);

/**
 * TeamMemberList Component
 *
 * Renders a list of team members.
 *
 * @param {object} props
 * @returns {object} - List of team members.
 */
const TeamMemberList = ({ members }) => (
    Div({ class: "space-y-3" },
        members.map(member => TeamMember(member))
    )
);

/**
 * TeamMemberCard Component
 *
 * This component renders the team members card with a header and list of members.
 *
 * @returns {object} - The complete team members card.
 */
export const TeamMemberCard = ({ members }) => (
    Card({ class: "w-full max-w-md mx-auto p-6 bg-card space-y-6" }, [
        CardHeader({ title: "Team Member", description: "Invite your team members to collaborate." }),

        TeamMemberList({ members })
    ])
);

export default TeamMemberCard;