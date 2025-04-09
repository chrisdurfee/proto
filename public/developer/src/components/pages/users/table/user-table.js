import { A, Div, P, Span, Td, Thead, Tr } from "@base-framework/atoms";
import { Badge, Checkbox } from "@base-framework/ui/atoms";
import { Avatar, StaticStatusIndicator } from "@base-framework/ui/molecules";
import { CheckboxCol, HeaderCol, ScrollableDataTable } from "@base-framework/ui/organisms";
import { UserModal } from "../modals/user-modal.js";

/**
 * This will create a permission modal.
 *
 * @param {object} item
 * @param {object} parent
 * @returns {object}
 */
const Modal = (item, { parent }) => (
	UserModal({
		item,
		onClose: (data) => parent.list.mingle([ data.get() ])
	})
);

/**
 * This will create a user avatar.
 *
 * @param {object} row
 * @return {object}
 */
const UserAvatar = (row) => (
	A({
		href: `users/${row.id}`,
		class: 'flex items-center gap-x-4 no-underline text-inherit hover:text-primary'
	}, [
		Div({ class: 'relative' }, [
			Avatar({
				src: row.image,
				alt: row.username,
				fallbackText: `${row.firstName} ${row.lastName}`
			}),
			StaticStatusIndicator(row.status)
		]),
		Div({ class: 'min-w-0 flex-auto' }, [
			Div({ class: 'flex items-center gap-2' }, [
				Span({ class: 'text-base font-semibold leading-6' }, `${row.firstName} ${row.lastName}`),
			]),
			P({ class: 'truncate text-sm leading-5 text-muted-foreground m-0' }, row.username)
		])
	])
);

/**
 * This will create a user roles.
 *
 * @param {object} row
 * @returns {Array}
 */
const UserRoles = (row) => (
	row.roles?.map(role =>
		Badge({ type: 'gray' }, role.name)
	)
);

/**
 * This will create a user row.
 *
* @param {object} row
* @param {function} onSelect
* @return {object}
*/
export const UserRow = (row, onSelect) => (
	Tr({ class: 'items-center px-4 py-2 hover:bg-muted/50 cursor-pointer', click: (e, parent) => Modal(row, parent)  }, [
		Td({ class: 'p-4 hidden md:table-cell' }, [
			new Checkbox({
				checked: row.selected,
				class: 'mr-2',
				onChange: () => onSelect(row)
			})
		]),
		Td({ class: 'p-4 hidden md:table-cell' }, String(row.id)),
		Td({ class: 'p-4' }, [
			UserAvatar(row)
		]),
		Td({ class: 'p-4 max-w-[200px] truncate' }, row.email),
		Td({ class: 'p-4 hidden md:table-cell' }, row.createdAt),
		Td({ class: 'p-4 hidden md:table-cell' }, row.emailVerifiedAt || '-'),
		Td({ class: 'p-4 flex flex-wrap gap-2' }, UserRoles(row))
	])
);

/**
 * This will create a header for the user table.
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
			HeaderCol({ key: 'emailVerifiedAt', label: 'Email Verified' }),
			HeaderCol({ key: 'roles', label: 'Roles' })
		])
	])
);

/**
 * This will create a user table.
 *
* @param {object} data
* @return {object}
*/
export const UserTable = (data) => (
	ScrollableDataTable({
		data,
		cache: 'list',
		customHeader: HeaderRow(),
		rows: [],
		limit: 50,
		rowItem: UserRow,
		key: 'id',
	})
);
