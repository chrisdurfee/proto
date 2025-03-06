import { Td, Thead, Tr } from "@base-framework/atoms";
import { Button, Checkbox } from "@base-framework/ui/atoms";
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
			HeaderCol({ key: 'errorFile', label: 'File', class: 'max-w-[150px] md:max-w-none' }),
			HeaderCol({ key: 'errorLine', label: 'Line Number' }),
			HeaderCol({ key: 'errorMessage', label: 'Message', class: 'max-w-[150px] md:max-w-none' }),
			HeaderCol({ key: 'createdAt', label: 'Date', class: 'hidden md:table-cell' }),
			HeaderCol({ key: 'env', label: 'Env' }),
			HeaderCol({ key: 'errorIp', label: 'IP' }),
			HeaderCol({ key: 'resolved', label: 'Resolved' })
		])
	])
);

/**
 * This will resolve the error.
 *
 * @param {object} props
 * @param {object} list
 * @returns {void}
 */
const resolveError = (props, { list }) =>
{
	const data = new ErrorModel({
		id: props.id,
		resolved: 1
	});

	data.xhr.updateResolved('', (response) =>
	{
		list.refresh();
	});
};

/**
 * This will unresolve the error.
 *
 * @param {object} props
 * @param {object} list
 * @returns {void}
 */
const unresolveError = (props, { list }) =>
{
	const data = new ErrorModel({
		id: props.id,
		resolved: 0
	});

	data.xhr.updateResolved('', (response) =>
	{
		list.refresh();
	});
};

/**
 * This will create a button to resolve the error.
 *
 * @param {object} props
 * @returns {object}
 */
const ResolveButton = (props) => (
	Button({
		variant: 'outline',
		text: 'Resolve',
		click(e, parent)
		{
			resolveError(props, parent);
		}
	})
);

/**
 * This will create a button to mark as unresolved.
 *
 * @param {object} props
 * @returns {object}
 */
const UnresolveButton = (props) => (
	Button({
		variant: 'outline',
		text: 'Unresolve',
		click(e, parent)
		{
			unresolveError(props, parent);
		}
	})
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
		Td({ class: 'p-4 truncate max-w-[150px] md:max-w-none' }, row.errorFile),
		Td({ class: 'p-4' }, row.errorLine),
		Td({ class: 'p-4 truncate max-w-[150px] md:max-w-none' }, row.errorMessage),
		Td({ class: 'p-4 hidden md:table-cell' }, row.createdAt),
		Td({ class: 'p-4' }, row.env),
		Td({ class: 'p-4' }, row.errorIp),
		Td({ class: 'p-4' }, [
			row.resolved === 1 ? UnresolveButton(row) : ResolveButton(row)
		])
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