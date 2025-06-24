import { Div, H1, On } from "@base-framework/atoms";
import { Button, Tooltip } from "@base-framework/ui/atoms";
import { Icons } from "@base-framework/ui/icons";
import { Page } from "@base-framework/ui/pages";
import { UserModal } from "../../../modals/user-modal.js";
import UserSkeleton from "../../user-skeleton.js";
import { UserContent } from "../user-content.js";
import UserHeader from "../user-header.js";

/**
 * ProfilePage
 *
 * Profile page for displaying user information.
 *
 * @returns {object}
 */
export const ProfilePage = () => (
	new Page({ class: 'flex flex-auto flex-col' }, [
		On("loaded", (loaded, ele, { context }) =>
		{
			if (!loaded)
			{
				return UserSkeleton();
			}

			const user = context.data.user;
			if (!user)
			{
				return Div({ class: "text-center" }, "User not found.");
			}

			return Div({ class: 'md:p-6 md:pt-0 2xl:mx-auto w-full 2xl:max-w-[1600px]' }, [
				Div({ class: 'flex flex-row justify-between gap-4' }, [
					Div({ class: 'flex flex-col' }, [
						H1({ class: 'text-2xl md:text-2xl font-bold tracking-tight' }, 'Profile'),
					]),
					Div({ class: 'flex flex-row space-x-2' }, [
						Div({ class: 'hidden lg:inline-flex' }, [
							Button({ variant: 'withIcon', class: 'text-muted-foreground outline', icon: Icons.pencil.square, click: () => UserModal({ item: user }) }, 'Edit'),
						]),
						Div({ class: 'flex lg:hidden mr-4' }, [
							Tooltip({ content: 'Edit', position: 'left' }, Button({ variant: 'icon', class: 'outline', icon: Icons.pencil.square, click: () => UserModal({ item: user }) }))
						])
					])
				]),
				Div({ class: 'flex flex-auto flex-col lg:flex-row lg:space-x-8'}, [
					Div({ class: 'w-full lg:w-1/3' }, [
						UserHeader({ user })
					]),
					Div({ class: 'flex-1' }, [
						UserContent({ user }),
					])
				])
			]);
		})
	])
);

export default ProfilePage;