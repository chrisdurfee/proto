import { Div, H1, Header } from "@base-framework/atoms";
import { Button, Tooltip } from "@base-framework/ui/atoms";
import { Icons } from "@base-framework/ui/icons";
import { DateRangePicker } from "@base-framework/ui/molecules";
import { getDate } from "./get-date.js";

/**
 * This will refresh the list.
 *
 * @param {object} e - The event object.
 * @param {object} parent - The parent object.
 * @returns {void}
 */
const refresh = (e, { list }) =>
{
	list.refresh();
};

/**
 * This will create a page header for the timeclock page.
 *
 * @returns {object}
 */
export const PageHeader = () => (
	Header({ class: 'flex flex-col pt-0 sm:pt-2 md:pt-0' }, [
		Div({ class: 'flex flex-auto items-center justify-between w-full' }, [
			H1({ class: 'text-3xl font-bold' }, 'Login Times'),
			Div({ class: 'flex items-center gap-2' }, [
				new DateRangePicker({
					start: getDate('start'),
					end: getDate('end'),
					onChange: (range, { data, list }) =>
					{
						data.dates = {
							start: range.start,
							end: range.end
						};

						list.refresh();
					}
				}),
				Div({ class: 'hidden lg:flex' }, [
					Button({ variant: 'withIcon', class: 'text-muted-foreground primary', icon: Icons.refresh, click: refresh }, 'Refresh')
				]),
				Div({ class: 'flex lg:hidden mr-0' }, [
					Tooltip({ content: 'Refresh', position: 'left' }, Button({ variant: 'icon', class: 'outline', icon: Icons.refresh, click: refresh }))
				]),
			])
		])
	])
);