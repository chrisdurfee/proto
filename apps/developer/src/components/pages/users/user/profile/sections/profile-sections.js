import { Div, P, Td, Tr } from "@base-framework/atoms";
import { Badge, Card } from "@base-framework/ui/atoms";
import { DataTable, DynamicDataTable } from "@base-framework/ui/organisms";
import { ProfileSection } from "./profile-section.js";

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
	ProfileSection({ title: "User Details" }, [
		Card({ class: "p-6", margin: "m-0", hover: true }, [
			Div({ class: "grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6" }, [
				Div({ class: "space-y-1" }, [
					P({ class: "text-sm text-muted-foreground" }, "User ID"),
					P({ class: "font-medium text-foreground" }, "[[user.id]]")
				]),

				Div({ class: "space-y-1" }, [
					P({ class: "text-sm text-muted-foreground" }, "UUID"),
					P({ class: "font-medium text-foreground truncate" }, "[[user.uuid]]")
				]),

				Div({ class: "space-y-1" }, [
					P({ class: "text-sm text-muted-foreground" }, "Date Created"),
					P({ class: "font-medium text-foreground" }, "[[user.createdAt]]")
				]),

				Div({ class: "space-y-1" }, [
					P({ class: "text-sm text-muted-foreground" }, "Birth Date"),
					P({ class: "font-medium text-foreground" }, "[[user.dob]]")
				]),

				Div({ class: "space-y-1" }, [
					P({ class: "text-sm text-muted-foreground" }, "Gender"),
					P({ class: "font-medium text-foreground capitalize" }, "[[user.gender]]")
				]),

				Div({ class: "space-y-1" }, [
					P({ class: "text-sm text-muted-foreground" }, "Allows Email"),
					P({ class: "font-medium text-foreground" }, "[[user.allowEmail]]")
				]),

				Div({ class: "space-y-1" }, [
					P({ class: "text-sm text-muted-foreground" }, "Allows Sms"),
					P({ class: "font-medium text-foreground" }, "[[user.allowSms]]")
				]),

				Div({ class: "space-y-1" }, [
					P({ class: "text-sm text-muted-foreground" }, "Allows Push"),
					P({ class: "font-medium text-foreground" }, "[[user.allowPush]]")
				])
			])
		])
	]);

/**
 * LocaleDetailsSection
 *
 * @returns {object}
 */
export const LocaleDetailsSection = () =>
	ProfileSection({ title: "Locale Details" }, [
		Card({ class: "p-6", margin: "m-0", hover: true }, [
			Div({ class: "grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6" }, [
				// Language
				Div({ class: "space-y-1" }, [
					P({ class: "text-sm text-muted-foreground" }, "Language"),
					P({ class: "font-medium text-foreground capitalize" }, "[[user.language]]")
				]),

				// Country
				Div({ class: "space-y-1" }, [
					P({ class: "text-sm text-muted-foreground" }, "Country"),
					P({ class: "font-medium text-foreground uppercase" }, "[[user.country]]")
				]),

				// Time Zone
				Div({ class: "space-y-1" }, [
					P({ class: "text-sm text-muted-foreground" }, "Time Zone"),
					P({ class: "font-medium text-foreground uppercase" }, "[[user.timezone]]")
				]),

				Div({ class: "space-y-1" }, [
					P({ class: "text-sm text-muted-foreground" }, "Currency"),
					P({ class: "font-medium text-foreground uppercase" }, "[[user.currency]]")
				])
			])
		])
	]);

/**
 * AppDetailsSection
 *
 * @returns {object}
 */
export const AppDetailsSection = () =>
	ProfileSection({ title: "Account Details" }, [
		Card({ class: "p-6", margin: "m-0", hover: true }, [
			Div({ class: "grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6" }, [
				Div({ class: "space-y-1" }, [
					P({ class: "text-sm text-muted-foreground" }, "Accepted Terms"),
					P({ class: "font-medium text-foreground" }, "[[user.acceptedTermsAt]]")
				]),

				Div({ class: "space-y-1" }, [
					P({ class: "text-sm text-muted-foreground" }, "Last Login"),
					P({ class: "font-medium text-foreground" }, "[[user.lastLoginAt]]")
				]),

				Div({ class: "space-y-1" }, [
					P({ class: "text-sm text-muted-foreground" }, "Enabled"),
					P({ class: "font-medium text-foreground" }, "[[user.enabled]]")
				]),

				Div({ class: "space-y-1" }, [
					P({ class: "text-sm text-muted-foreground" }, "Email Verified"),
					P({ class: "font-medium text-foreground" }, "[[user.emailVerifiedAt]]")
				]),

				Div({ class: "space-y-1" }, [
					P({ class: "text-sm text-muted-foreground" }, "Allows Marketing"),
					P({ class: "font-medium text-foreground" }, "[[user.marketingOptIn]]")
				]),

				Div({ class: "space-y-1" }, [
					P({ class: "text-sm text-muted-foreground" }, "Last Updated"),
					P({ class: "font-medium text-foreground" }, "[[user.updatedAt]]")
				]),

				Div({ class: "space-y-1" }, [
					P({ class: "text-sm text-muted-foreground" }, "Follower Count"),
					P({ class: "font-medium text-foreground" }, "[[user.followerCount]]")
				]),

				Div({ class: "space-y-1" }, [
					P({ class: "text-sm text-muted-foreground" }, "Password Changed"),
					P({ class: "font-medium text-foreground" }, "[[user.lastPasswordChangeAt]]")
				]),
			])
		])
	]);

/**
 * PersonalDetailsSection
 *
 * @returns {object}
 */
export const PersonalDetailsSection = () =>
	ProfileSection({ title: "Trial Details" }, [
		Div([
			Div({ class: "grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6" }, [
				// Trial mode
				Div({ class: "space-y-1" }, [
					P({ class: "text-sm text-muted-foreground" }, "Trial Mode"),
					P({ class: "font-medium text-foreground" }, "[[user.trialMode]]")
				]),

				// Marketing
				Div({ class: "space-y-1" }, [
					P({ class: "text-sm text-muted-foreground" }, "Days Remaining"),
					P({ class: "font-medium text-foreground" }, "[[user.trialDaysLeft]] days left"),
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
export const ScheduleSection = () =>
{
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
 * @param {string} props.bio - User bio text.
 * @returns {object}
 */
export const AboutSection = ({ bio }) =>
	ProfileSection({ title: "Bio"}, [
		P({ class: "text-base text-muted-foreground" }, bio)
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

/**
 * AuthedDeviceSection
 *
 * @param {object} props
 * @returns {object}
 */
export const AuthedDeviceSection = (props) =>
	ProfileSection({ title: "Authed Devices" }, [
		Div({ class: "overflow-x-auto" },
			DynamicDataTable({
				key: 'id',
				headers: [
					{ label: 'Platform', key: 'platform' },
					{ label: 'Brand', key: 'brand' },
					{ label: 'Version', key: 'version' }
				],
				rows: [],
				limit: 50,
				rowItem: (device) => Tr({ class: "items-center px-4 py-2 hover:bg-muted/50" }, [
					Td({ class: "p-4" }, P(device.platform)),
					Td({ class: "p-4 text-muted-foreground" }, P(device.brand)),
					Td({ class: "p-4" }, Badge({ variant: "primary" }, device.version))
				])
			})
		)
	]);

/**
 * OrganizationSection
 *
 * @param {object} props
 * @param {Array} props.organizations - Array of organization objects with name, role, and status.
 * @returns {object}
 */
export const OrganizationSection = ({ organizations }) =>
	ProfileSection({ title: "Organizations" }, [
		Div({ class: "overflow-x-auto" },
			new DataTable({
				key: 'id',
				headers: [
					{ label: 'ID', key: 'id' },
					{ label: 'Name', key: 'name' },
					{ label: 'Status', key: 'status' }
				],
				rows: organizations,
				rowItem: (org) => Tr({ class: "items-center px-4 py-2 hover:bg-muted/50" }, [
					Td({ class: "p-4" }, String(org.id)),
					Td({ class: "p-4 text-muted-foreground" }, P(org.name)),
					Td({ class: "p-4" }, Badge({ variant: "primary" }, 'Active'))
				])
			})
		)
	]);