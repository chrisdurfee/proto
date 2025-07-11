import { A, Div, P, Span, Td, Thead, Tr } from "@base-framework/atoms";
import { Badge, Button, Checkbox } from "@base-framework/ui/atoms";
import { Icons } from "@base-framework/ui/icons";
import { Avatar, EmptyState, StaticStatusIndicator } from "@base-framework/ui/molecules";
import { CheckboxCol, HeaderCol, ScrollableDataTable } from "@base-framework/ui/organisms";
import { UserModal } from "../modals/user-modal.js";

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
	Tr({ class: 'items-center px-4 py-2 hover:bg-muted/50 cursor-pointer'  }, [
		Td({ class: 'p-4 hidden md:table-cell' }, [
			new Checkbox({
				checked: row.selected,
				class: 'mr-2',
				onChange: () => onSelect(row)
			})
		]),
		Td({ class: 'p-4 hidden md:table-cell' }, [
			A({ href: `users/${row.id}`, class: 'text-muted-foreground' }, String(row.id))
		]),
		Td({ class: 'p-4' }, [
			UserAvatar(row)
		]),
		Td({ class: 'p-4 max-w-[200px] truncate hidden md:table-cell' }, [
			A({ href: `mailto:${row.email}`, class: 'text-muted-foreground', 'data-cancel-route': true }, row.email)
		]),
		Td({ class: 'p-4 hidden md:table-cell' }, [
			A({ href: `users/${row.id}`, class: 'text-muted-foreground' }, row.createdAt)
		]),
		Td({ class: 'p-4 hidden md:table-cell' }, [
			A({ href: `users/${row.id}`, class: 'text-muted-foreground' }, row.emailVerifiedAt || '-')
		]),
		Td({ class: 'p-4 hidden md:table-cell flex-wrap gap-2' }, [
			A({ href: `users/${row.id}`, class: 'text-muted-foreground' }, UserRoles(row))
		])
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
			HeaderCol({ key: 'email', label: 'Email', class: 'hidden md:table-cell' }),
			HeaderCol({ key: 'createdAt', label: 'Created At', class: 'hidden md:table-cell' }),
			HeaderCol({ key: 'emailVerifiedAt', label: 'Email Verified', class: 'hidden md:table-cell' }),
			HeaderCol({ key: 'roles', label: 'Roles', class: 'hidden md:table-cell' })
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
		emptyState: () => EmptyState({
			title: 'Feels Empty!',
			description: 'No users have been found. Maybe create a new user.',
			icon: Icons.user.minus
		}, [
			Button({ variant: 'withIcon', icon: Icons.user.plus, click: (e, parent) => UserModal({
				onClose: (data) =>
				{
					// @ts-ignore
					parent.list.refresh();
				}
			}) }, 'Add User')
		])
	})
);
