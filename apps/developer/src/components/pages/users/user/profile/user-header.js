import { A, Div, H1 } from "@base-framework/atoms";
import { Avatar, Tooltip } from "@base-framework/ui";
import { Button } from "@base-framework/ui/atoms";
import { Icons } from "@base-framework/ui/icons";
import { StaticStatusIndicator } from "@base-framework/ui/molecules";

/**
 * UserHeader
 *
 * Displays the user header, including the image, name, and metadata.
 *
 * @param {object} props
 * @param {object} props.user - The user data.
 * @returns {object}
 */
export const UserHeader = ({ user }) => (
	Div({ class: "flex flex-col gap-2 mt-4" }, [
		// User Image
		Div({ class: 'flex flex-auto items-center justify-center mt-6' }, [
			Div({ class: 'relative mt-6 flex-none' }, [
				Avatar({
					src: '[[user.image]]',
					alt: '[[user.firstName]] [[user.lastName]]',
					watcherFallback: '[[user.firstName]] [[user.lastName]]',
					size: "4xl",
				}),
				Div({ class: "absolute bottom-3 right-3" }, [
					StaticStatusIndicator(user.status)
				])
			])
		]),

		// User Name
		Div({ class: "text-sm text-muted-foreground flex gap-1 flex-col truncate items-center justify-center" }, [
			H1({ class: "text-3xl font-bold text-foreground truncate mt-4 capitalize" }, `[[user.firstName]] [[user.lastName]]`),
		]),

		// User Metadata
		Div({ class: "text-sm text-muted-foreground flex gap-1 flex-col truncate items-center justify-center" }, [
			Div({ class: 'text-xl capitalize' }, '[[user.displayName]]'),
			Div({ class: 'uppercase' }, '[[user.country]]'),
		]),

		// phone and email
		Div({ class: 'text-sm text-muted-foreground gap-1 flex-col truncate items-center justify-center mt-4 hidden md:flex' }, [
			Div('[[user.email]]'),
			Div('[[user.mobile]]'),
		]),
		Div({ class: 'flex flex-auto items-center justify-center' }, [
			Div({ class: 'flex space-x-4 mt-4' }, [
				Tooltip({ content: 'Email' }, [
					A({ href: `mailto:[[user.email]]`, class: 'text-muted-foreground', 'data-cancel-route': true }, [
						Button({ variant: 'icon', icon: Icons.envelope.default, label: 'Email' })
					])
				]),
				Tooltip({ content: 'Call' }, [
					A({ href: `tel:[[user.mobile]]`, class: 'text-muted-foreground', 'data-cancel-route': true }, [
						Button({ variant: 'icon', icon: Icons.phone.default, label: 'Call' })
					])
				]),
				Tooltip({ content: 'Message' }, [
					Button({ variant: 'icon', icon: Icons.chat.text, label: 'Message' })
				]),
				Tooltip({ content: 'More' }, [
					Button({ variant: 'icon', icon: Icons.ellipsis.vertical, label: 'More' })
				])
			])
		])
	])
);

export default UserHeader;