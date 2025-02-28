import { Div } from "@base-framework/atoms";
import { Fieldset, Input } from "@base-framework/ui/atoms";
import { Icons } from "@base-framework/ui/icons";
import { FormField, Modal } from "@base-framework/ui/molecules";

/**
 * This will create a modal for adding a resource.
 *
 * @param {object} props
 * @returns {object}
 */
export const GeneratorModal = (props) => (
    new Modal({
		title: 'Add Resource',
		icon: Icons.document.add,
		description: "Let's add a new resource.",
		size: 'sm',
		type: 'right',
		// @ts-ignore
		onSubmit: () => app.notify({
			type: "success",
			title: "Resource Added",
			description: "The resource has been added.",
			icon: Icons.check
		})
	}, [
		Div({ class: 'flex flex-col lg:p-4 space-y-8' }, [
			// Row for Area and Security Level
			Div({ class: "flex flex-auto flex-col w-full gap-4" }, [
				Fieldset({ legend: "Resource Details" }, [
					new FormField({ name: "resource", label: "Resource", description: "The name of the resource." }, [
						Input({
							type: "text",
							placeholder: "Resource name",
							required: true
						})
					])
				])
			])
		])
	]).open()
);