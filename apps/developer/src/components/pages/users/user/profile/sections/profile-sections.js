import { Div, H2, Header, P, Td, Tr } from "@base-framework/atoms";
import { Atom } from "@base-framework/base";
import { Card } from "@base-framework/ui";
import { Badge, Button, Icon } from "@base-framework/ui/atoms";
import { Icons } from "@base-framework/ui/icons";
import { DataTable } from "@base-framework/ui/organisms";

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
					P({ class: "text-sm text-muted-foreground" }, "Employee ID"),
					P({ class: "font-medium text-foreground" }, "EMP-000123")
				]),

				// Date Started
				Div({ class: "space-y-1" }, [
					P({ class: "text-sm text-muted-foreground" }, "Date Started"),
					P({ class: "font-medium text-foreground" }, "Jan. 1, 2020")
				]),

				// Time-to-Hire
				Div({ class: "space-y-1" }, [
					P({ class: "text-sm text-muted-foreground" }, "Time-to-Hire"),
					P({ class: "font-medium text-foreground" }, "45 days")
				]),

				// Years at Company
				Div({ class: "space-y-1" }, [
					P({ class: "text-sm text-muted-foreground" }, "Years at Company"),
					P({ class: "font-medium text-foreground" }, "3 years")
				]),

				// Last Promotion Date
				Div({ class: "space-y-1" }, [
					P({ class: "text-sm text-muted-foreground" }, "Last Promotion Date"),
					P({ class: "font-medium text-foreground" }, "Jun. 15, 2022")
				]),

				// Department
				Div({ class: "space-y-1" }, [
					P({ class: "text-sm text-muted-foreground" }, "Department"),
					P({ class: "font-medium text-foreground" }, "Engineering")
				]),

				// Reporting Manager
				Div({ class: "space-y-1" }, [
					P({ class: "text-sm text-muted-foreground" }, "Reporting Manager"),
					P({ class: "font-medium text-foreground" }, "Jordan Smith")
				]),

				// Office / Time Zone
				Div({ class: "space-y-1" }, [
					P({ class: "text-sm text-muted-foreground" }, "Office / Time Zone"),
					P({ class: "font-medium text-foreground" }, "Dubai / GST")
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
	ProfileSection({ title: "Personal Details" }, [
		Div([
			Div({ class: "grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6" }, [
				// Work Style
				Div({ class: "space-y-1" }, [
					P({ class: "text-sm text-muted-foreground" }, "Work Style"),
					P({ class: "font-medium text-foreground" }, "Hybrid")
				]),

				// Emergency Contact
				Div({ class: "space-y-1" }, [
					P({ class: "text-sm text-muted-foreground" }, "Emergency Contact"),
					P({ class: "font-medium text-foreground" }, "Alex Doe"),
					P({ class: "text-sm text-muted-foreground" }, "(555) 123-4567")
				]),

				// Birthday
				Div({ class: "space-y-1" }, [
					P({ class: "text-sm text-muted-foreground" }, "Birthday"),
					P({ class: "font-medium text-foreground" }, "Jun. 7th")
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
 * PositionHistorySection
 *
 * “Work History” list with icons and a thin vertical line.
 *
 * @param {object} props
 * @param {Array} props.history - Array of position history objects.
 *
 * @returns {object}
 */
export const PositionHistorySection = ({ history }) =>
	ProfileSection({ title: "Position History" }, [
		Div({ class: "relative pl-6 space-y-6" }, [
			history.map((position, index) => (
				Div({ class: "flex flex-auto flex-row space-x-4" }, [
					Div({ class: 'flex flex-col relative items-center' }, [
						Icon(Icons.breifcase),
						// vertical line
						(index !== (history.length - 1))
							? Div({ class: "h-full w-px bg-border" })
							: null
					]),
					Div({ class: "flex flex-col pb-8" }, [
						P({ class: "font-medium" }, position.title),
						P({ class: "text-sm text-muted-foreground" }, `(${position.from} - ${position.to})`)
					])
				])
			))
		])
	]);

/**
 * ProjectsSection
 *
 * Plain table with header row and subtle row borders.
 *
 * @param {object} props
 * @param {Array} props.projects - Array of project objects with name, role, and status.
 * @returns {object}
 */
export const ProjectsSection = ({ projects }) =>
	ProfileSection({ title: "Current Projects" }, [
		Div({ class: "overflow-x-auto" },
			new DataTable({
				key: 'id',
				headers: [
					{ label: 'Project Name', key: 'name' },
					{ label: 'Role', key: 'role' },
					{ label: 'Status', key: 'status' }
				],
				rows: projects,
				rowItem: (project) => Tr({ class: "items-center px-4 py-2 hover:bg-muted/50" }, [
					Td({ class: "p-4" }, P(project.name)),
					Td({ class: "p-4 text-muted-foreground" }, P(project.role)),
					Td({ class: "p-4" }, Badge({ variant: project.status === "Completed" ? "secondary" : "primary" }, project.status))
				])
			})
		)
	]);

/**
 * SkillsSection
 *
 * Inline badges, wrapped.
 *
 * @param {object} props
 * @param {Array} props.skills - Array of skill strings.
 * @returns {object}
 */
export const SkillsSection = ({ skills }) =>
	ProfileSection({ title: "Skills" }, [
		Div({ class: "space-y-4" }, [
			Div({ class: "flex flex-wrap gap-2" }, skills.map(skill => Badge({ variant: "outline" }, skill)))
		])
	]);

/**
 * ReviewsSection
 *
 * Another plain table for performance reviews.
 *
 * @param {object} props
 * @param {Array} props.reviews - Array of review objects with date, reviewer, and rating.
 * @returns {object}
 */
export const ReviewsSection = ({ reviews }) =>
	ProfileSection({ title: "Performance Reviews", description: "User performance reviews" }, [
		Div({ class: "overflow-x-auto" }, [
			new DataTable({
				key: 'id',
				headers: [
					{ label: 'Date', key: 'date' },
					{ label: 'Reviewer', key: 'reviewer' },
					{ label: 'Rating', key: 'rating' }
				],
				rows: reviews,
				rowItem: (review) => Tr({ class: "items-center px-4 py-2 hover:bg-muted/50" }, [
					Td({ class: "p-4" }, P(review.date)),
					Td({ class: "p-4 text-muted-foreground" }, P(review.reviewer)),
					Td({ class: "p-4" }, P(review.rating))
				])
			})
		])
	]);

/**
 * CancelledRequestItem
 *
 * Renders a single cancelled time-off request row.
 *
 * @param {object} props
 * @param {string} props.typeLabel
 * @param {string} props.startDate
 * @param {string} props.endDate
 * @param {number} props.days
 * @param {number} props.percent
 * @param {string} props.icon
 * @param {string} props.status
 * @returns {object}
 */
export const CancelledRequestItem = Atom((props) =>
	Card({ class: "flex items-center justify-between p-4 bg-card rounded-lg", margin: 'm-0', hover: true }, [
		Div({ class: "flex items-center space-x-4" }, [
			Div({ class: "flex items-center bg-muted rounded-full p-4" }, [
				Icon(props.icon)
			]),
			Div({ class: "flex flex-col" }, [
				P({ class: "font-medium" }, props.typeLabel),
				P(
					{ class: "text-sm text-muted-foreground" },
					`${props.startDate} → ${props.endDate} (${props.days}d) • ${props.percent}%`
				)
			])
		]),
		Badge({ variant: "outline" }, props.status)
	])
);

/**
 * PendingRequestItem
 *
 * Renders a single pending time-off request row, with action buttons.
 *
 * @param {object} props
 * @param {string} props.typeLabel
 * @param {string} props.startDate
 * @param {number} props.days
 * @param {number} props.percent
 * @param {string} props.icon
 * @param {string} props.status
 * @param {string} [props.reason]
 * @returns {object}
 */
export const PendingRequestItem = Atom((props) =>
	Card({ class: "flex flex-col space-y-2 p-4 bg-card rounded-lg", margin: 'm-0', hover: true }, [
		Div({ class: "flex items-center justify-between" }, [
			Div({ class: "flex items-center space-x-4" }, [
				Div({ class: "flex items-center bg-muted rounded-full p-4" }, [
					Icon(props.icon)
				]),
				Div({ class: "flex flex-col" }, [
					P({ class: "font-medium" }, props.typeLabel),
					P(
						{ class: "text-sm text-muted-foreground" },
						`${props.startDate} (${props.days}d) • ${props.percent}%`
					)
				])
			]),
			Div({ class: "flex items-center space-x-2" }, [
				Badge({ variant: "secondary" }, props.status),
				Button({ variant: "icon", class: 'outline', icon: Icons.check, label: "Approve" }),
				Button({ variant: "icon", class: 'outline', icon: Icons.x, label: "Decline" })
			])
		]),
		props.reason && P({ class: "text-sm text-muted-foreground bg-muted p-2 rounded" }, props.reason)
	])
);

/**
 * TimeOffRequestsSection
 *
 * Displays all time-off requests using the item atoms.
 *
 * @returns {object}
 */
export const TimeOffRequestsSection = () =>
	ProfileSection(
		{
			title: "Time Off Requests",
			onAction: () => Button({ variant: "outline", icon: Icons.plus.default, label: "Register time-off" })
		},
		[
			Div({ class: "space-y-3", margin: "m-0" }, [
				CancelledRequestItem({
					icon: Icons.airplane,
					typeLabel: "Vacation",
					startDate: "Jul 24, 2024",
					endDate: "Jul 31, 2024",
					days: 6,
					percent: 100,
					status: "Cancelled"
				}),

				PendingRequestItem({
					icon: Icons.face.frown,
					typeLabel: "Sick",
					startDate: "Jul 3, 2024",
					days: 1,
					percent: 100,
					status: "Pending",
					reason: "Needing the day off."
				})
			])
		]
	);