import { Div, On, Span } from "@base-framework/atoms";
import { Skeleton } from "@base-framework/ui/atoms";
import { Avatar } from "@base-framework/ui/molecules";

/**
 * Displays the user's avatar and name in a styled container.
 *
 * @returns {Array<object>}
 */
const UserDetails = () => [
	Avatar({ src: '[[user.image]]', alt: '[[user.firstName]] [[user.lastName]]', watcherFallback: '[[user.firstName]] [[user.lastName]]', size: "sm" }),
	Div({ class: "flex flex-auto flex-col" }, [
		Span({ class: "text-sm truncate" }, '[[user.firstName]]'),
		Span({ class: "text-xs truncate text-muted-foreground" }, '[[user.lastName]]'),
	])
];

/**
 * Displays a skeleton loader for user details.
 *
 *  @returns {Array<object>}
 */
const UserDetailsSkeleton = () => [
	Skeleton({ width: "w-9", height: "h-9", shape: "circle" }),
	Div({ class: "flex flex-auto flex-col space-y-1" }, [
		Skeleton({ width: "w-32", height: "h-3" }),
		Skeleton({ width: "w-40", height: "h-3" }),
	])
];

/**
 * Displays the user's avatar and name in a styled container.
 *
 * @returns {object}
 */
export const UserAvatar = () => (
	On('loaded', (value =>
	{
		return Div({ class: "flex items-center space-x-4 border rounded-md p-2 hover:bg-muted/50" },
			(!value)
			? UserDetailsSkeleton()
			: UserDetails()
		);
	}))
);