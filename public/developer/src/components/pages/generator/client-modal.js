import { Div } from "@base-framework/atoms";
import { Fieldset, Input } from "@base-framework/ui/atoms";
import { Icons } from "@base-framework/ui/icons";
import { FormField, Modal } from "@base-framework/ui/molecules";

/**
 * This will create a modal for adding music.
 *
 * @param {object} props
 * @returns {object}
 */
export const ClientModal = (props) => (
    new Modal({
		title: 'Add Client',
		icon: Icons.user.plus,
		description: "Let's add a new client.",
		size: 'sm',
		type: 'right',
		// @ts-ignore
		onSubmit: () => app.notify({
			type: "success",
			title: "Client Added",
			description: "The client has been added.",
			icon: Icons.check
		})
	}, [
		Div({ class: 'flex flex-col lg:p-4 space-y-8' }, [
			// Row for Area and Security Level
			Div({ class: "flex flex-auto flex-col w-full gap-4" }, [
				Fieldset({ legend: "Client Details" }, [
					new FormField({ name: "client", label: "Client", description: "The name of the client." }, [
						Input({
							type: "text",
							placeholder: "Client name",
							required: true
						})
					])
				])
			])
		])
	]).open()
);