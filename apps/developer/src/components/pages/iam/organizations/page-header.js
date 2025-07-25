import { Div, H1, Header } from "@base-framework/atoms";
import { Button, Tooltip } from "@base-framework/ui/atoms";
import { Icons } from "@base-framework/ui/icons";
import { OrganizationModal } from "./modals/organization-modal.js";

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
 * This will create a permission modal.
 *
 * @param {object} item
 * @param {object} parent
 * @returns {object}
 */
const Modal = (item, parent) => (
	OrganizationModal({
		item,
		onClose: (data) => parent.list.refresh()
	})
);

/**
 * This will create a page header for the organizations page.
 *
 * @returns {object}
 */
export const PageHeader = () => (
	Header({ class: 'flex flex-col' }, [
		Div({ class: 'flex flex-auto items-center justify-between w-full' }, [
			H1({ class: 'text-3xl font-bold' }, 'Organizations'),
			Div({ class: 'flex items-center gap-2' }, [
				Div({ class: 'hidden lg:flex' }, [
					Button({ variant: 'withIcon', class: 'text-muted-foreground outline', icon: Icons.refresh, click: refresh }, 'Refresh')
				]),
				Div({ class: 'hidden lg:flex' }, [
					Button({ variant: 'withIcon', class: 'text-muted-foreground primary', icon: Icons.circlePlus, click: (e, parent) => Modal(null, parent) }, 'Add Organization')
				]),
				Div({ class: 'flex lg:hidden mr-0' }, [
					Tooltip({ content: 'Add Organization', position: 'left' }, Button({ variant: 'icon', class: 'outline', icon: Icons.circlePlus, click: (e, parent) => Modal(null, parent) }))
				])
			])
		])
	])
);