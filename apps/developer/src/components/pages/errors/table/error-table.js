import { Td, Thead, Tr } from "@base-framework/atoms";
import { Checkbox } from "@base-framework/ui/atoms";
import { Icons } from "@base-framework/ui/icons";
import { EmptyState } from "@base-framework/ui/molecules";
import { CheckboxCol, HeaderCol, ScrollableDataTable } from "@base-framework/ui/organisms";
import { ErrorModal } from "../modals/error-modal.js";
import { ResultButtons } from "./result-buttons.js";

/**
 * This will create a permission modal.
 *
 * @param {object} item
 * @param {object} parent
 * @returns {object}
 */
const Modal = (item, { parent }) => (
	ErrorModal({
		error: item,
		onClose: (data) => parent.list.mingle([ data.get() ])
	}).open()
);

/**
 * This will render a header row in the table.
 *
 * @returns {object}
 */
const HeaderRow = () => (
	Thead([
		Tr({ class: 'text-muted-foreground border-b' }, [
			CheckboxCol({ class: 'hidden md:table-cell' }),
			HeaderCol({ key: 'errorFile', label: 'File', class: 'max-w-[150px]' }),
			HeaderCol({ key: 'errorLine', label: 'Line' }),
			HeaderCol({ key: 'errorMessage', label: 'Message', class: 'max-w-[150px]' }),
			HeaderCol({ key: 'createdAt', label: 'Date', class: 'hidden md:table-cell' }),
			HeaderCol({ key: 'env', label: 'Env' }),
			HeaderCol({ key: 'errorIp', label: 'IP' }),
			HeaderCol({ key: 'resolved', label: 'Resolved' })
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
	Tr({
		class: 'items-center px-4 py-2 hover:bg-muted/50 cursor-pointer',
		click: (e, parent) => Modal(row, parent)
	}, [
		Td({ class: 'p-4 hidden md:table-cell' }, [
			new Checkbox({
				checked: row.selected,
				class: 'mr-2',
				onChange: () => onSelect(row)
			})
		]),
		Td({ class: 'p-4 truncate max-w-[150px]' }, row.errorFile),
		Td({ class: 'p-4' }, String(row.errorLine)),
		Td({ class: 'p-4 truncate max-w-[150px]' }, row.errorMessage),
		Td({ class: 'p-4 hidden md:table-cell' }, row.createdAt),
		Td({ class: 'p-4' }, row.env),
		Td({ class: 'p-4' }, row.errorIp),
		Td({ class: 'p-4' }, [
			new ResultButtons({
				id: row.id,
				resolved: row.resolved
			})
		])
	])
);

/**
 * This will create a table.
 *
 * @param {object} data
 * @returns {object}
 */
export const ErrorTable = (data) => (
	ScrollableDataTable({
		data,
		cache: 'list',
		customHeader: HeaderRow(),
		rows: [],
		limit: 50,
		rowItem: Row,
		key: 'id',
		emptyState: () => EmptyState({
			title: 'Well Done!',
			description: 'No errors found. Your coding skills are impressive!',
			icon: Icons.circleCheck
		})
	})
);