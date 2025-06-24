import { Div, H2, Header, P } from "@base-framework/atoms";
import { Atom } from "@base-framework/base";
import { Badge, Card } from "@base-framework/ui/atoms";

/**
 * ProfileSection
 *
 * Generic section with a title and description, used for various profile sections.
 * @param {object} props
 * @param {string} props.title - Section title.
 * @param {string} props.description - Section description.
 * @param {Array} children - Child components to render within the section.
 * @returns {object}
 */
export const ProfileSection = Atom((props, children) => (
	Div({ class: "space-y-6" }, [
		Header({ class: "flex flex-col space-y-2" }, [
			H2({ class: "text-xl font-semibold" }, props.title),
			props.description && P({ class: "text-sm text-muted-foreground" }, props.description)
		]),
		...children
	])
))

/**
 * OrgDetailsSection
 *
 * Organization-specific employment fields:
 * – Employee ID
 * – Date Started
 * – Time-to-Hire
 * – Years at Company
 * – Last Promotion Date
 * – Department
 * – Reporting Manager
 * – Office / Time Zone
 *
 * @returns {object}
 */
export const OrgDetailsSection = () =>
	ProfileSection({ title: "Organization Details" }, [
		Card({ class: "p-6", margin: "m-0", hover: true }, [
			Div({ class: "grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6" }, [
				// Employee ID
				Div({ class: "space-y-1" }, [
					P({ class: "text-sm text-muted-foreground" }, "User ID"),
					P({ class: "font-medium text-foreground" }, "[[user.id]]")
				]),

				// Date Created
				Div({ class: "space-y-1" }, [
					P({ class: "text-sm text-muted-foreground" }, "Date Created"),
					P({ class: "font-medium text-foreground" }, "[[user.createdAt]]")
				]),

				// Birth Date
				Div({ class: "space-y-1" }, [
					P({ class: "text-sm text-muted-foreground" }, "Birth Date"),
					P({ class: "font-medium text-foreground" }, "[[user.dob]]")
				]),

				// Accepted Terms
				Div({ class: "space-y-1" }, [
					P({ class: "text-sm text-muted-foreground" }, "Accepted Terms"),
					P({ class: "font-medium text-foreground" }, "[[user.acceptedTermsAt]]")
				]),

				// Last Login
				Div({ class: "space-y-1" }, [
					P({ class: "text-sm text-muted-foreground" }, "Last Login"),
					P({ class: "font-medium text-foreground" }, "[[user.lastLoginAt]]")
				]),

				// Language
				Div({ class: "space-y-1" }, [
					P({ class: "text-sm text-muted-foreground" }, "Language"),
					P({ class: "font-medium text-foreground" }, "[[user.language]]")
				]),

				// Country
				Div({ class: "space-y-1" }, [
					P({ class: "text-sm text-muted-foreground" }, "Country"),
					P({ class: "font-medium text-foreground" }, "[[user.country]]")
				]),

				// Time Zone
				Div({ class: "space-y-1" }, [
					P({ class: "text-sm text-muted-foreground" }, "Time Zone"),
					P({ class: "font-medium text-foreground" }, "[[user.timezone]]")
				])
			])
		])
	]);

/**
 * PersonalDetailsSection
 *
 * User-specific personal fields:
 * – Work Style
 * – Emergency Contact
 * – Birthday
 *
 * @returns {object}
 */
export const PersonalDetailsSection = () =>
	ProfileSection({ title: "Account Details" }, [
		Div([
			Div({ class: "grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6" }, [
				// Trial mode
				Div({ class: "space-y-1" }, [
					P({ class: "text-sm text-muted-foreground" }, "Trial Mode"),
					P({ class: "font-medium text-foreground" }, "[[user.trialMode]] ([[user.trialDaysLeft]] days left)")
				]),

				// Marketing
				Div({ class: "space-y-1" }, [
					P({ class: "text-sm text-muted-foreground" }, "Allows Marketing"),
					P({ class: "font-medium text-foreground" }, "[[user.marketingOptIn]]"),
				]),

				// Email Verified
				Div({ class: "space-y-1" }, [
					P({ class: "text-sm text-muted-foreground" }, "Email Verified"),
					P({ class: "font-medium text-foreground" }, "[[user.emailVerifiedAt]]")
				])
			])
		])
	]);


/**
 * ScheduleSection
 *
 * Placeholder weekly in/out times.
 *
 * @returns {object}
 */
export const ScheduleSection = () => {
	const days = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];
	return ProfileSection({ title: "Schedule" }, [
		Card({ class: '', margin: 'm-0' }, [
			Div({ class: "grid grid-cols-7 text-sm text-muted-foreground divide-x divide-border" },
				days.map((day) =>
					(day == 'Sat' || day == 'Sun')
					? Div({ class: "flex flex-col items-center space-y-1 p-2 bg-card rounded" }, [
						P({ class: "font-medium text-foreground" }, day),
						P("-")
					])
					: Div({ class: "flex flex-col items-center space-y-1 p-2 bg-card rounded hover:bg-muted/50" }, [
						P({ class: "font-medium text-foreground flex-col" }, day),
						P("9:00 am"),
						P("5:00 pm")
					])
				)
			)
		])
	]);
};

/**
 * AboutSection
 *
 * Simple header + paragraph, no card.
 *
 * @param {object} props
 * @param {string} props.about - User about text.
 * @returns {object}
 */
export const AboutSection = ({ about }) =>
	ProfileSection({ title: "About"}, [
		P({ class: "text-base text-muted-foreground" }, about)
	]);

/**
 * ContactSection
 *
 * Two-column label/value list with separators.
 *
 * @param {object} props
 * @returns {object}
 */
export const ContactSection = ({ user }) =>
	ProfileSection({ title: "Contact Information", description: "User contact details" }, [
		Div({ class: "divide-y divide-muted-200 text-sm text-muted-foreground" }, [
			Div({ class: "flex justify-between py-2" }, [
				P({ class: "font-medium text-foreground" }, "Email"),
				P({ class: "truncate" }, user.email)
			]),
			Div({ class: "flex justify-between py-2" }, [
				P({ class: "font-medium text-foreground" }, "Phone"),
				P({ class: "truncate" }, user.phone || "—")
			])
		])
	]);

/**
 * Creates a role section.
 *
 * @param {object} props
 * @param {Array} props.roles - Array of role strings.
 * @returns {object}
 */
export const RoleSection = ({ roles }) =>
	ProfileSection({ title: "Roles" }, [
		Div({ class: "space-y-4" }, [
			Div({ class: "flex flex-wrap gap-2" }, roles.map(role => Badge({ variant: "outline" }, role.name)))
		])
	]);