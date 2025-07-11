import { Div, H1, Header } from "@base-framework/atoms";
import { Button, Tooltip } from "@base-framework/ui/atoms";
import { Icons } from "@base-framework/ui/icons";
import { Combobox } from "@base-framework/ui/molecules";

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
 * This will create a dropdown for the page.
 *
 * @returns {object}
 */
const Dropdown = () => (
	new Combobox({
		width: 'w-full', // this is the default value
		maxWidth: 'max-w-[250px]', // this is the default value
		class: '',
		selectFirst: true,
		onSelect: (item, { data, list }) =>
		{
			const val = item.value;
			if (val === 'all')
			{
				data.filter = '';
				list.refresh();
				return;
			}

			const lowerVal = val.toLowerCase();
			data.filter = lowerVal;
			list.refresh();
		},
		items: [
			{ value: 'all', label: 'All' },
			{ value: 'dev', label: 'Dev'},
			{ value: 'testing', label: 'Testing' },
			{ value: 'staging', label: 'Staging' },
			{ value: 'prod', label: 'Prod' },
		]
	})
);

/**
 * This will create a page header for the errors page.
 *
 * @returns {object}
 */
export const PageHeader = () => (
	Header({ class: 'flex flex-col' }, [
		Div({ class: 'flex flex-auto items-center justify-between w-full' }, [
			H1({ class: 'text-3xl font-bold' }, 'Error Log'),
			Div({ class: 'flex items-center gap-2' }, [
				Div({ class: 'hidden lg:flex' }, [
					Button({ variant: 'withIcon', class: 'text-muted-foreground outline', icon: Icons.refresh, click: refresh }, 'Refresh')
				]),
				Div({ class: 'flex lg:hidden mr-0' }, [
					Tooltip({ content: 'Refresh', position: 'left' }, Button({ variant: 'icon', class: 'outline', icon: Icons.refresh, click: refresh }))
				]),
				Dropdown()
			])
		])
	])
);