import { Div, H1, Header } from "@base-framework/atoms";
import { Icons } from "@base-framework/ui/icons";
import { BackButton } from "@base-framework/ui/organisms";
import { SidebarMenu } from "./sidebar-menu.js";
import { UserAvatar } from "./user-avatar.js";

/**
 * Toolbar
 *
 * Displays a back button in the toolbar.
 *
 * @returns {object}
 */
const Toolbar = () => (
	Div({ class: "flex w-full flex-col space-y-8 pb-8 mt-4 md:mt-0 md:pl-2" }, [
		Div({ class: "flex items-center justify-between" }, [
			Header({ class: 'flex space-x-4 items-center' }, [
				BackButton({
					margin: 'm-0 ml-0',
					backUrl: '/directory'
				}),
				H1({ class: 'scroll-m-20 text-lg lg:text-lg font-bold tracking-tight truncate' }, '[[user.firstName]] [[user.lastName]]'),
			])
		]),
		UserAvatar()
	])
);

/**
 * This will create the Sidebar.
 *
 * @param {object} props
 * @returns {object}
 */
export const Sidebar = ({ userId }) => (
	SidebarMenu({
		topNav: Toolbar(),
		options: [
			{ label: 'Profile', href: `directory/user/${userId}`, icon: Icons.identification, exact: true },
			{ label: 'Timeclock', href: `directory/user/${userId}/timeclock`, icon: Icons.calendar.default },
			{
				group: 'Resources',
				options: [
					{ label: 'Documents', href: `directory/user/${userId}/documents`, icon: Icons.document.text },
					{ label: 'Notes', href: `directory/user/${userId}/notes`, icon: Icons.pencil.square },
					{ label: 'Posts', href: `directory/user/${userId}/posts`, icon: Icons.newspaper },
				]
			},
			{
				group: 'Performance',
				options: [
					{ label: '1-on-1', href: `directory/user/${userId}/one-on-one`, icon: Icons.user.multiple, exact: false },
					{ label: 'Analytics', href: `directory/user/${userId}/analytics`, icon: Icons.barChart },
					{ label: 'Reviews', href: `directory/user/${userId}/reviews`, icon: Icons.folder.default },
				]
			}
		]
	})
);