import { OnState, Td, Thead, Tr } from "@base-framework/atoms";
import { Component, Jot } from "@base-framework/base";
import { Button, Checkbox } from "@base-framework/ui/atoms";
import { Icons } from "@base-framework/ui/icons";
import { CheckboxCol, HeaderCol, ScrollableDataTable } from "@base-framework/ui/organisms";
import { ErrorModal } from "./modals/error-modal.js";
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
			HeaderCol({ key: 'errorFile', label: 'File', class: 'max-w-[150px]' }),
			HeaderCol({ key: 'errorLine', label: 'Line Number' }),
			HeaderCol({ key: 'errorMessage', label: 'Message', class: 'max-w-[150px]' }),
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
 * @param {object} parent
 * @returns {void}
 */
const resolveError = (props, parent) =>
{
	const data = new ErrorModel({
		id: props.id,
		resolved: 1
	});

	data.xhr.updateResolved('', (response) =>
	{
	});
};

/**
 * This will unresolve the error.
 *
 * @param {object} props
 * @param {object} parent
 * @returns {void}
 */
const unresolveError = (props, parent) =>
{
	const data = new ErrorModel({
		id: props.id,
		resolved: 0
	});

	data.xhr.updateResolved('', (response) =>
	{
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
		variant: 'withIcon',
		class: 'outline',
		icon: Icons.circleCheck,
		click(e, parent)
		{
			e.preventDefault();
			e.stopPropagation();

			resolveError(props, parent);
			parent.state.resolved = 1;
		}
	}, 'Resolve')
);

/**
 * This will create a button to mark as unresolved.
 *
 * @param {object} props
 * @returns {object}
 */
const UnresolveButton = (props) => (
	Button({
		variant: 'withIcon',
		class: 'outline',
		icon: Icons.circleX,
		click(e, parent)
		{
			e.preventDefault();
			e.stopPropagation();

			unresolveError(props, parent);
			parent.state.resolved = 0;
		}
	}, 'Unresolve')
);

/**
 * This will render the button.
 *
 * @type {typeof Component}
 */
const ResultButtons = Jot(
{
	/**
	 * @type {object}
	 */
	// @ts-ignore
	state()
	{
		return {
			// @ts-ignore
			resolved: this.resolved
		};
	},

	/**
	 * This will render the button.
	 *
	 * @returns {object}
	 */
	// @ts-ignore
	render()
	{
		const props = {
			// @ts-ignore
			id: this.id,
		};

		return OnState('resolved', (resolved) => (resolved === 1 ? UnresolveButton(props) : ResolveButton(props)));
	}
});

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
		click: () => ErrorModal({
			error: row
		}).open()
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
		rowItem: Row,
		key: 'id',
	})
);