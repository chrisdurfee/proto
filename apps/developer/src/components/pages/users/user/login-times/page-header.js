import { Div, H1, Header } from "@base-framework/atoms";
import { Button, Tooltip } from "@base-framework/ui/atoms";
import { Icons } from "@base-framework/ui/icons";

/**
 * This will create a page header for the timeclock page.
 *
 * @returns {object}
 */
export const PageHeader = () => (
	Header({ class: 'flex flex-auto flex-col pt-0 sm:pt-2 md:pt-0' }, [
		Div({ class: 'flex flex-auto items-center justify-between w-full' }, [
			H1({ class: 'text-3xl font-bold' }, 'Login Times'),
			Div({ class: 'flex items-center gap-2' }, [
				Div({ class: 'hidden lg:flex' }, [
					Button({ variant: 'withIcon', class: 'text-muted-foreground outline', icon: Icons.clock, click: () => null }, 'Clock In/Out'),
					Button({ variant: 'withIcon', class: 'text-muted-foreground outline ml-2', icon: Icons.circlePlus, click: () => null }, 'Add Entry')
				]),
				Div({ class: 'flex lg:hidden mr-0' }, [
					Tooltip({ content: 'Clock In/Out', position: 'left' }, Button({ variant: 'icon', class: 'outline mr-2', icon: Icons.clock, click: () => null })),
					Tooltip({ content: 'Add Entry', position: 'left' }, Button({ variant: 'icon', class: 'outline', icon: Icons.circlePlus, click: () => null }))
				])
			])
		])
	])
);