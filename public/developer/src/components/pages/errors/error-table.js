import { Td, Thead, Tr } from "@base-framework/atoms";
import { Checkbox } from "@base-framework/ui/atoms";
import { CheckboxCol, HeaderCol, ScrollableDataTable } from "@base-framework/ui/organisms";
import { ErrorModel } from "./models/error-model.js";

/**
 * This will render a header row in the table.
 *
 * @returns {object}
 */
const HeaderRow = () => (
	Thead([
		Tr({ class: 'text-muted-foreground border-b' }, [
			CheckboxCol({ class: 'hidden md:table-cell' }),
			HeaderCol({ key: 'id', label: 'ID', class: 'hidden md:table-cell' }),
			HeaderCol({ key: 'error', label: 'Error', class: 'max-w-[150px] md:max-w-none' }),
			HeaderCol({ key: 'timestamp', label: 'Timestamp', class: 'hidden md:table-cell' }),
			HeaderCol({ key: 'groupId', label: 'Group ID', align: 'justify-end' })
		])
	])
);

/**
 * This will render a row in the table.
 *
 * @param {object} row - Row data
 * @param {function} onSelect - Selection callback
 * @returns {object}
 */
export const Row = (row, onSelect) => (
	Tr({ class: 'items-center px-4 py-2 hover:bg-muted/50' }, [
		Td({ class: 'p-4 hidden md:table-cell' }, [
			new Checkbox({
				checked: row.selected,
				class: 'mr-2',
				onChange: () => onSelect(row)
			})
		]),
		Td({ class: 'p-4 hidden md:table-cell' }, String(row.id)),
		Td({ class: 'p-4 truncate max-w-[150px] md:max-w-none' }, row.migration),
		Td({ class: 'p-4 hidden md:table-cell' }, row.createdAt),
		Td({ class: 'p-4 text-right justify-end' }, String(row.groupId))
	])
);

/**
 * This will create a table.
 *
 * @returns {object}
 */
export const ErrorTable = () => (
	ScrollableDataTable({
		data: new ErrorModel(),
		cache: 'list',
		customHeader: HeaderRow(),
		rows: [],
		rowItem: Row,
		key: 'id',
	})
);