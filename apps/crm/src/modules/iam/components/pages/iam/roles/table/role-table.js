import { Td, Thead, Tr } from "@base-framework/atoms";
import { Checkbox } from "@base-framework/ui/atoms";
import { CheckboxCol, HeaderCol, ScrollableDataTable } from "@base-framework/ui/organisms";
import { RoleModal } from "../modals/role-modal";

/**
 * This will create a permission modal.
 *
 * @param {object} item
 * @param {object} parent
 * @returns {object}
 */
const Modal = (item, { parent }) => (
	RoleModal({
		item,
		onClose: (data) => parent.list.mingle([ data.get() ])
	})
);

/**
 * This will create a role row.
 *
 * @param {object} row
 * @param {function} onSelect
 * @return {object}
 */
export const RoleRow = (row, onSelect) => (
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
		Td({ class: 'p-4 truncate max-w-[150px]' }, row.slug),
		Td({ class: 'p-4 truncate max-w-[200px]' }, row.description),
		Td({ class: 'p-4 truncate max-w-[200px] capitalize' }, row.resource),
		Td({ class: 'p-4 hidden md:table-cell' }, row.createdAt)
	])
);

/**
 * This will create a header for the role table.
 *
 * @return {object}
 */
const HeaderRow = () => (
	Thead([
		Tr({ class: 'text-muted-foreground border-b' }, [
			CheckboxCol({ class: 'hidden md:table-cell' }),
			HeaderCol({ key: 'id', label: 'ID' }),
			HeaderCol({ key: 'name', label: 'Name' }),
			HeaderCol({ key: 'slug', label: 'Slug' }),
			HeaderCol({ key: 'description', label: 'Description' }),
			HeaderCol({ key: 'resource', label: 'Resource' }),
			HeaderCol({ key: 'createdAt', label: 'Created At', class: 'hidden md:table-cell' })
		])
	])
);

/**
 * This will create a role table.
 *
 * @param {object} data
 * @return {object}
 */
export const RoleTable = (data) => (
	ScrollableDataTable({
		data,
		cache: 'list',
		customHeader: HeaderRow(),
		rows: [],
		limit: 50,
		rowItem: RoleRow,
		key: 'id',
	})
);
