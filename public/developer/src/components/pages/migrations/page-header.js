import { Div, H1, Header } from "@base-framework/atoms";
import { Button, Tooltip } from "@base-framework/ui/atoms";
import { Icons } from "@base-framework/ui/icons";
import { Confirmation } from "@base-framework/ui/molecules";
import { MigrationModel } from "./models/migration-model";

/**
 * Thi swill revert the last migration.
 *
 * @param {object} e - The event object.
 * @param {object} parent - The parent object.
 * @returns {void}
 */
const revert = (e, parent) =>
{
	new Confirmation({
		icon: Icons.circleMinus,
		type: 'destructive',
		title: 'Are you absolutely sure?',
		description: 'Are you sure you want to revert the last migration?',
		confirmTextLabel: 'Confirm',
		confirmed: () => update('down', parent)
	}).open();
};

/**
 * This will run the migration.
 *
 * @param {object} e - The event object.
 * @param {object} parent - The parent object.
 * @returns {void}
 */
const run = (e, parent) =>
{
	new Confirmation({
		icon: Icons.circlePlus,
		title: 'Are you absolutely sure?',
		description: 'Are you sure you want to run the migration?',
		confirmTextLabel: 'Confirm',
		confirmed: () => update('up', parent)
	}).open();
};

/**
 * This will update the migration.
 *
 * @param {string} direction - The direction to update.
 * @param {object} parent - The parent object.
 * @returns {void}
 */
const update = (direction, { list }) =>
{
	const data = new MigrationModel();
	data.xhr.update({direction: direction}, (response) =>
	{
		if (response)
		{
			list.refresh();
		}
	});
};

/**
 * This will create a page header for the clients page.
 *
 * @returns {object}
 */
export const PageHeader = () => (
	Header({ class: 'flex flex-auto flex-col' }, [
		Div({ class: 'flex flex-auto items-center justify-between w-full' }, [
			H1({ class: 'text-3xl font-bold' }, 'Migrations'),
			Div({ class: 'flex items-center gap-2' }, [
				Div({ class: 'hidden lg:flex' }, [
					Button({ variant: 'withIcon', class: 'text-muted-foreground outline', icon: Icons.circleMinus, click: revert }, 'Revert')
				]),
				Div({ class: 'flex lg:hidden mr-0' }, [
					Tooltip({ content: 'Revert Migration', position: 'left' }, Button({ variant: 'icon', class: 'outline', icon: Icons.circleMinus, click: revert }))
				]),
				Div({ class: 'hidden lg:flex' }, [
					Button({ variant: 'withIcon', class: 'text-muted-foreground', icon: Icons.circlePlus, click: run }, 'Run')
				]),
				Div({ class: 'flex lg:hidden mr-0' }, [
					Tooltip({ content: 'Run Migration', position: 'left' }, Button({ variant: 'icon', class: 'outline', icon: Icons.circlePlus, click: run }))
				])
			])
		])
	])
);