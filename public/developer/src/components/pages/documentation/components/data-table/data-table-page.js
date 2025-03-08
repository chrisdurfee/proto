import { H4, P, Section, Span, Td, Tr } from '@base-framework/atoms';
import { Checkbox } from "@base-framework/ui/atoms";
import { DataTable, ScrollableDataTable } from "@base-framework/ui/organisms";
import { CodeCard } from "../../../../molecules/cards.js";
import { DocSection } from "../../../../molecules/doc-section.js";
import { DocPage } from '../../../doc-page.js';
import { CustomHeaderTable } from './custom-header-table.js';

/** Example table configuration */
const headers = [
	{ label: 'checkbox', key: '' },
	{ label: 'Status', key: 'status' },
	{ label: 'Email', key: 'email' },
	{ label: 'Amount', key: 'amount', align: 'justify-end' }
];

/** Example row data */
const rows = [
	{ id: 1, status: 'Success', email: 'ken99@yahoo.com', amount: 316.00, selected: false },
	{ id: 2, status: 'Success', email: 'abe45@gmail.com', amount: 242.00, selected: false },
	{ id: 3, status: 'Processing', email: 'monserrat44@gmail.com', amount: 837.00, selected: false },
	{ id: 4, status: 'Success', email: 'silas22@gmail.com', amount: 874.00, selected: false },
	{ id: 5, status: 'Failed', email: 'carmella@hotmail.com', amount: 721.00, selected: false }
];

/**
 * Creates a row item (TR) with a checkbox for selection.
 *
 * @param {object} row - Row data
 * @param {function} onSelect - Callback to handle row selection
 * @returns {object}
 */
const rowItem = (row, onSelect) =>
{
	return Tr({ class: 'items-center px-4 py-2 hover:bg-muted/50' }, [
		Td({ class: 'p-4' }, [
			new Checkbox({
				checked: row.selected,
				class: 'mr-2',
				onChange: () => onSelect(row)
			})
		]),
		Td({ class: 'p-4 ' }, [
			Span({ class: 'text-muted-foreground' }, row.status)
		]),
		Td({ class: 'p-4 ' }, row.email),
		Td({ class: 'p-4 text-right' }, `$${row.amount.toFixed(2)}`)
	]);
};

/**
 * DataTablePage
 *
 * Documents DataTable usage, including advanced row manipulation (selecting, removing, etc.),
 * and now includes a section on the ScrollableDataTable which automatically loads more rows as you scroll.
 *
 * @returns {DocPage}
 */
export const DataTablePage = () =>
	DocPage(
	{
		title: 'Data Tables',
		description: 'Data tables are used to display or manipulate lists of items in tabular form.'
	},
	[
		// 1) Basic usage with a preview
		DocSection({
			title: 'Usage',
			description: 'The DataTable component displays items in a structured format, supporting selection, custom row items, and more.',
			preview: [
				new DataTable({ headers, rows, rowItem, key: 'id' }),
			],
			code: `import { DataTable } from "@base-framework/ui/organisms";
import { Checkbox } from "@base-framework/ui/atoms";
import { Tr, Td } from '@base-framework/atoms';

const headers = [
	{ label: 'checkbox', key: '' },
	{ label: 'Status', key: 'status' },
	{ label: 'Email', key: 'email' },
	{ label: 'Amount', key: 'amount', align: 'justify-end' }
];

const rows = [
	{ id: 1, status: 'Success', email: 'ken99@yahoo.com', amount: 316.00, selected: false },
	// ...
];

const rowItem = (row, onSelect) =>
{
	return Tr({ class: 'items-center px-4 py-2 hover:bg-muted/50' }, [
		Td({ class: 'p-4' }, [
			new Checkbox({
				checked: row.selected,
				onChange: () => onSelect(row)
			})
		]),
		Td({ class: 'p-4 ' }, row.status),
		Td({ class: 'p-4 ' }, row.email),
		Td({ class: 'p-4 text-right' }, \`$\${row.amount.toFixed(2)}\`)
	]);
};

new DataTable({
	key: 'id',
	headers,
	rows,
	rowItem
});`
		}),

		// 2) Custom header usage with a preview
		DocSection({
			title: 'Custom Header Data Table',
			description: 'Demonstrates a data table with custom headers and row rendering using the DataTable component.',
			preview: [
				CustomHeaderTable()
			],
			code: `// custom-header-table.js
import { CheckboxCol, HeaderCol } from "@base-framework/ui/organisms";
import { DataTable } from "@base-framework/ui/organisms";
import { Tr, Td, Thead } from '@base-framework/atoms';

// Showcases a custom header with CheckboxCol and HeaderCol, plus a custom rowItem.
export const CustomHeaderTable = () => {
   // define custom rows, rowItem, etc.
   return new DataTable({
	   key: 'id',
	   customHeader: ...,
	   rows: ...,
	   rowItem
   });
};`
		}),

		// 3) Scrollable Data Table section
		DocSection({
			title: 'Scrollable Data Table',
			description: 'The ScrollableDataTable component extends the basic DataTable by automatically loading additional rows as you scroll near the bottom of the table\'s container. It provides a refresh() method to reload data. You can pass a loadMoreItems callback for dynamic fetching or pass a data object to have the callback set up automatically.',
			preview: [
				ScrollableDataTable({
					cache: 'table',
					headers,
					rows,
					rowItem,
					loadMoreItems: (offset, limit, callback) =>
					{
						// Simulate asynchronous data fetching.
						setTimeout(() =>
						{
							callback([
								{ id: 6, status: 'Success', email: 'newuser@example.com', amount: 200.00, selected: false }
							]);
						}, 1000);
					},
					scrollContainer: window,
					limit: 20
				})
			],
			code: `import { ScrollableDataTable } from "@base-framework/organisms";

const headers = [
	{ label: 'Status', key: 'status' },
	{ label: 'Email', key: 'email' },
	{ label: 'Amount', key: 'amount', align: 'justify-end' }
];

const rows = [
	{ id: 1, status: 'Success', email: 'ken99@yahoo.com', amount: 316.00, selected: false },
	// additional rows...
];

const scrollableTable = ScrollableDataTable({
	cache: 'table',
	headers,
	rows,
	rowItem,
	loadMoreItems: (offset, limit, callback) => {
		// Replace with asynchronous data fetching logic.
		setTimeout(() => {
			callback([
				{ id: 6, status: 'Success', email: 'newuser@example.com', amount: 200.00, selected: false }
			]);
		}, 1000);
	},
	scrollContainer: window,
	limit: 20
});

// To refresh the table data:
scrollableTable.refresh();`
		}),

		// 4) Selecting & resetting
		Section({ class: 'space-y-4 mt-8' }, [
			H4({ class: 'text-lg font-bold' }, 'Selecting and Resetting Rows'),
			P({ class: 'text-muted-foreground' }, 'DataTable supports toggling all rows, clearing selections, and retrieving selected rows.'),
			CodeCard(`// Suppose 'dt' is your DataTable instance

// Select or reset selection
dt.toggleAllSelectedRows(); // toggles between all selected or none

// Retrieve selected rows
const selectedItems = dt.getSelectedRows();
console.log('Selected Rows:', selectedItems);

// Clear the selection entirely
dt.data.selectedRows = [];
dt.updateSelected(); // updates the internal 'selected' boolean
`)
		]),

		// 5) Removing items
		Section({ class: 'space-y-4 mt-8' }, [
			H4({ class: 'text-lg font-bold' }, 'Removing Items'),
			P({ class: 'text-muted-foreground' }, 'Use remove() to delete rows by passing an array of items (or a single item).'),
			CodeCard(`// dt is your DataTable instance

// Removing a single row
dt.remove([ rowToRemove ]);

// or removing multiple
dt.remove(selectedItems); // e.g. dt.getSelectedRows()
`)
		]),

		// 6) Appending items
		Section({ class: 'space-y-4 mt-8' }, [
			H4({ class: 'text-lg font-bold' }, 'Appending Items'),
			P({ class: 'text-muted-foreground' }, 'Use append() to add rows at the end of the list. Accepts an array or single object.'),
			CodeCard(`// dt is your DataTable instance
const newRow = { id: 6, status: 'New', email: 'newuser@example.com', amount: 100 };
dt.append(newRow);

// or multiple
dt.append([
	{ id: 7, status: 'Queued', email: 'queue@example.com', amount: 200 },
	{ id: 8, status: 'Processing', email: 'process@example.com', amount: 350 }
]);
`)
		]),

		// 7) Mingling items
		Section({ class: 'space-y-4 mt-8' }, [
			H4({ class: 'text-lg font-bold' }, 'Mingling Items'),
			P({ class: 'text-muted-foreground' }, 'Mingle merges new items with existing items by matching keys. If withDelete = true, items not in newItems are removed.'),
			CodeCard(`// dt is your DataTable instance
const newItems = [
	{ id: 2, status: 'Success', email: 'abe45@gmail.com', amount: 242.00 },
	{ id: 9, status: 'Pending', email: 'another@example.com', amount: 500.00 }
];

// withDelete=true removes items not in newItems
dt.mingle(newItems, true);
`)
		]),

		// 8) Prepending items
		Section({ class: 'space-y-4 mt-8' }, [
			H4({ class: 'text-lg font-bold' }, 'Prepending Items'),
			P({ class: 'text-muted-foreground' }, 'Use prepend() to add new rows at the beginning of the list, shifting existing rows down.'),
			CodeCard(`// dt is your DataTable instance
const topRow = { id: 0, status: 'Top', email: 'top@example.com', amount: 999.99 };
dt.prepend(topRow);

// or multiple
dt.prepend([
	{ id: -1, status: 'Urgent', email: 'urgent@example.com', amount: 777 },
	{ id: -2, status: 'Priority', email: 'priority@example.com', amount: 555 }
]);
`)
		])
	]
);

export default DataTablePage;