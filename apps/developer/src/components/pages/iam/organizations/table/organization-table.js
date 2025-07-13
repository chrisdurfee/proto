import { Td, Thead, Tr } from "@base-framework/atoms";
import { Button, Checkbox } from "@base-framework/ui/atoms";
import { Icons } from "@base-framework/ui/icons";
import { EmptyState } from "@base-framework/ui/molecules";
import { CheckboxCol, HeaderCol, ScrollableDataTable } from "@base-framework/ui/organisms";
import { OrganizationModal } from "../modals/organization-modal.js";

/**
 * This will create a permission modal.
 *
 * @param {object} item
 * @param {object} parent
 * @returns {object}
 */
const Modal = (item, { parent }) => (
	OrganizationModal({
		item,
		onClose: (data) => parent.list.mingle([ data.get() ])
	})
);

/**
 * This will create a row.
 *
 * @param {object} row
 * @param {function} onSelect
 * @return {object}
 */
export const OrganizationRow = (row, onSelect) => (
	Tr({ class: 'items-center px-4 py-2 hover:bg-muted/50 cursor-pointer', click: (e, parent) => Modal(row, parent) }, [
		Td({ class: 'p-4 hidden md:table-cell' }, [
			new Checkbox({
				checked: row.selected,
				class: 'mr-2',
				onChange: () => onSelect(row)
			})
		]),
		Td({ class: 'p-4' }, String(row.id)),
		Td({ class: 'p-4 truncate max-w-[150px]' }, row.name),
		Td({ class: 'p-4 hidden md:table-cell' }, row.createdAt)
	])
);

/**
 * This will create a header for the permission table.
 *
 * @return {object}
 */
const HeaderRow = () => (
	Thead([
		Tr({ class: 'text-muted-foreground border-b' }, [
			CheckboxCol({ class: 'hidden md:table-cell' }),
			HeaderCol({ key: 'id', label: 'ID' }),
			HeaderCol({ key: 'name', label: 'Name' }),
			HeaderCol({ key: 'createdAt', label: 'Created At', class: 'hidden md:table-cell' })
		])
	])
);

/**
 * This will create a organization table.
 *
 * @param {object} data
 * @return {object}
 */
export const OrganizationTable = (data) => (
	ScrollableDataTable({
		data,
		cache: 'list',
		customHeader: HeaderRow(),
		rows: [],
		limit: 50,
		rowItem: OrganizationRow,
		key: 'id',
		emptyState: () => EmptyState({
			title: 'Missing Some Permissions',
			description: 'No permissions have been found.',
			icon: Icons.locked
		}, [
			Button({ variant: 'withIcon', icon: Icons.circlePlus, click: (e, parent) => Modal(null, parent) }, 'Add Permission')
		])
	})
);
