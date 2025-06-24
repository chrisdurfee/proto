import { A, Div, P, Span, Td, Thead, Tr } from "@base-framework/atoms";
import { Checkbox } from "@base-framework/ui/atoms";
import { Avatar } from "@base-framework/ui/molecules";
import { CheckboxCol, DataTable, HeaderCol } from "@base-framework/ui/organisms";

/**
 * This will render a header row for the timeclock table.
 *
 * @returns {object}
 */
const HeaderRow = () => (
	Thead([
		Tr({ class: 'text-muted-foreground border-b' }, [
			CheckboxCol({ class: 'hidden md:table-cell' }),
			HeaderCol({ key: 'employeeName', label: 'Employee'}),
			HeaderCol({ key: 'date', label: 'Date', class: 'hidden md:table-cell'  }),
			HeaderCol({ key: 'clockIn', label: 'Clock In', class: 'hidden md:table-cell'  }),
			HeaderCol({ key: 'clockOut', label: 'Clock Out', class: 'hidden md:table-cell'  }),
			HeaderCol({ key: 'hoursWorked', label: 'Hours Worked', class: 'hidden md:table-cell'  }),
			HeaderCol({ key: 'status', label: 'Status' }),
			HeaderCol({ key: 'department', label: 'Department', class: 'hidden md:table-cell'  }),
			HeaderCol({ key: 'hourlyRate', label: 'Rate', align: 'justify-end' })
		])
	])
);

/**
 * This will render a timeclock entry row.
 *
 * @param {object} row - Row data containing the timeclock entry
 * @param {function} onSelect - Selection callback
 * @returns {object}
 */
export const TimeclockRow = (row, onSelect) => (
	Tr({ class: 'items-center px-4 py-2 hover:bg-muted/50' }, [
		Td({ class: 'p-4 hidden md:table-cell' }, [
			new Checkbox({
				checked: row.selected,
				class: 'mr-2',
				onChange: () => onSelect(row)
			})
		]),
		Td({ class: 'p-4' }, [
			A({
				href: `employee/${row.id}`,
				class: 'flex items-center gap-x-4 no-underline text-inherit hover:text-primary',
			}, [
				Avatar({ src: row.employeeImage, alt: row.employeeName, fallbackText: row.employeeName }),
				Div({ class: 'min-w-0 flex-auto' }, [
					P({ class: 'text-base font-semibold leading-6 m-0' }, row.employeeName),
					P({ class: 'truncate text-sm leading-5 text-muted-foreground m-0' }, row.email)
				])
			])
		]),
		Td({ class: 'p-4 hidden md:table-cell' }, [
			A({
				href: `timeclock/${row.id}`,
				class: 'no-underline text-inherit hover:text-primary',
			}, row.date)
		]),
		Td({ class: 'p-4 hidden md:table-cell' }, [
			A({
				href: `timeclock/${row.id}`,
				class: 'no-underline text-inherit hover:text-primary',
			}, row.clockIn)
		]),
		Td({ class: 'p-4 hidden md:table-cell' }, [
			A({
				href: `timeclock/${row.id}`,
				class: 'no-underline text-inherit hover:text-primary',
			}, row.clockOut)
		]),
		Td({ class: 'p-4 hidden md:table-cell' }, [
			A({
				href: `timeclock/${row.id}`,
				class: 'no-underline text-inherit hover:text-primary',
			}, `${row.hoursWorked.toFixed(1)}`)
		]),
		Td({ class: 'p-4' }, [
			Span({
				class: `inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ${row.status === 'Active' ? 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20' : 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-700/10'}`
			}, row.status)
		]),
		Td({ class: 'p-4 hidden md:table-cell' }, [
			A({
				href: `timeclock/${row.id}`,
				class: 'no-underline text-inherit hover:text-primary',
			}, row.department)
		]),
		Td({ class: 'p-4 text-right justify-end' }, [
			A({
				href: `timeclock/${row.id}`,
				class: 'no-underline text-inherit hover:text-primary',
			}, `$${row.hourlyRate.toFixed(2)}`)
		])
	])
);

/**
 * This will create a timeclock table displaying employee time entries.
 *
 * @param {object} props - Component properties
 * @param {Array<object>} props.times - List of time entries
 * @returns {object}
 */
export const TimeclockTable = ({ times }) => (
	new DataTable({
		cache: 'timeclock',
		customHeader: HeaderRow(),
		rows: times,
		rowItem: TimeclockRow,
		key: 'id',
	})
);