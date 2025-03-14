import { A, Div, P, Span, Td, Thead, Tr } from "@base-framework/atoms";
import { Badge, Checkbox } from "@base-framework/ui/atoms";
import { Avatar, StaticStatusIndicator } from "@base-framework/ui/molecules";
import { CheckboxCol, HeaderCol, ScrollableDataTable } from "@base-framework/ui/organisms";

/**
 * This will create a role row.
 *
* @param {object} row
* @param {function} onSelect
* @return {object}
*/
export const RoleRow = (row, onSelect) => (
	Tr({ class: 'items-center px-4 py-2 hover:bg-muted/50 cursor-pointer' }, [
		Td({ class: 'p-4 hidden md:table-cell' }, [
			new Checkbox({
				checked: row.selected,
				class: 'mr-2',
				onChange: () => onSelect(row)
			})
		]),
		Td({ class: 'p-4 hidden md:table-cell' }, row.id),
		Td({ class: 'p-4' }, [
			A({
				href: `users/${row.id}`,
				class: 'flex items-center gap-x-4 no-underline text-inherit hover:text-primary'
			}, [
				Avatar({
					src: row.image,
					alt: row.username,
					fallbackText: `${row.firstName?.charAt(0) || ''}${row.lastName?.charAt(0) || ''}`
				}),
				Div({ class: 'min-w-0 flex-auto' }, [
					Div({ class: 'flex items-center gap-2' }, [
						Span({ class: 'text-base font-semibold leading-6' }, `${row.firstName} ${row.lastName}`),
						StaticStatusIndicator(row.status)
					]),
					P({ class: 'truncate text-sm leading-5 text-muted-foreground m-0' }, row.username)
				])
			])
		]),
		Td({ class: 'p-4 max-w-[200px] truncate' }, row.email),
		Td({ class: 'p-4 hidden md:table-cell' }, row.createdAt),
		Td({ class: 'p-4 hidden md:table-cell' }, row.emailVerifiedAt),
		Td({ class: 'p-4 flex flex-wrap gap-2' }, [
			row.roles?.map(role =>
				Badge({ type: 'gray' }, role.name)
			)
		])
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
			HeaderCol({ key: 'id', label: 'ID', class: 'hidden md:table-cell' }),
			HeaderCol({ key: 'name', label: 'Name' }),
			HeaderCol({ key: 'email', label: 'Email' }),
			HeaderCol({ key: 'createdAt', label: 'Created At' }),
			HeaderCol({ key: 'emailVerifiedAt', label: 'Email Verified At' }),
			HeaderCol({ key: 'roles', label: 'Roles' })
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
