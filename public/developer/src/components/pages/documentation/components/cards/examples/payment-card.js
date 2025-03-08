import { Button as BaseButton, Div, Span } from "@base-framework/atoms";
import { Atom, Component, Jot } from "@base-framework/base";
import { Button, Card, Icon, Input, Select } from "@base-framework/ui/atoms";
import { Icons } from "@base-framework/ui/icons";
import { Form, FormField } from "@base-framework/ui/molecules";
import { CardHeader } from "./card-atoms.js";

/**
 * This will create a state button.
 *
 * @param {object} props
 * @returns {object}
 */
const MethodButton = Atom(({ value, label, icon }) => (
	BaseButton({
		class: 'text-sm gap-1 font-medium leading-none disabled:cursor-not-allowed disabled:opacity-70 flex flex-col items-center justify-between rounded-md border-2 border-muted bg-popover p-4 hover:bg-muted data-[state="active"]:border-primary [&:has([data-state="active"])]:border-primary',
		onState: ['method', { active: value}],
		dataSet: ['method', ['state', value, 'active']],
		click: (e, {state}) => state.method = value
	}, [
		Icon(icon),
		Span(label)
	])
));

/**
 * ButtonGroup
 *
 * This will create a button group.
 *
 * @type {typeof Component}
 */
export const PaymentMethoGroup = Jot(
{
	/**
	 * This will render the component.
	 *
	 * @returns {object}
	 */
	render()
	{
		return Div({ class: 'flex flex-auto flex-col' }, [
			Div({ class: 'grid grid-cols-3 gap-4'}, [
				MethodButton({ label: 'Card', value: 'card', icon: Icons.creditCard }),
				MethodButton({ label: 'Paypal', value: 'paypal', icon: Icons.companies.paypal }),
				MethodButton({ label: 'Apple', value: 'apple', icon: Icons.companies.apple })
			])
		]);
	},

	/**
	 * This will setup the states.
	 *
	 * @returns {object}
	 */
	state()
	{
		return {
			method: null
		};
	}
});

/**
 * PaymentMethodSelector
 *
 * Creates the payment method selection buttons.
 *
 * @returns {object}
 */
const PaymentMethodSelector = () => (
	Div({ class: "flex items-center justify-center gap-4" }, [
		new PaymentMethoGroup()
	])
);

/**
 * PaymentForm
 *
 * Creates the form for payment details (Name, Card number, Expiry, Year, CVC).
 *
 * @returns {object}
 */
const PaymentForm = () => (
	Form({ class: 'space-y-4', submit: (e) => { e.preventDefault(); console.log("Payment method added"); } }, [
		// Name on Card field
		new FormField({
			name: "name",
			label: "Name",
		}, [
			Input({
				type: "text",
				placeholder: "First Last",
				required: true,
				class: "border p-2 rounded-md w-full bg-background placeholder-muted-foreground text-foreground"
			})
		]),

		// Card Number field
		new FormField({
			name: "card_number",
			label: "Card number",
		}, [
			Input({
				type: "text",
				placeholder: "1234 5678 9012 3456",
				required: true,
				class: "border p-2 rounded-md w-full bg-background placeholder-muted-foreground text-foreground"
			})
		]),

		// Expiry Date, Year, and CVC
		Div({ class: "flex gap-4 mt-4" }, [
			// Expiry Month
			new FormField({ name: "month", label: "Expires" }, [
				Select({
					required: true,
					class: "border p-2 rounded-md w-full bg-background text-foreground",
					options: [
						{ value: "", label: "Month", disabled: true },
						...Array.from({ length: 12 }, (_, i) => ({
							value: `${i + 1}`,
							label: `${i + 1}`.padStart(2, "0")
						}))
					]
				})
			]),

			// Expiry Year
			new FormField({ name: "year", label: "Year" }, [
				Select({
					required: true,
					class: "border p-2 rounded-md w-full bg-background text-foreground",
					options: [
						{ value: "", label: "Year", disabled: true },
						...Array.from({ length: 10 }, (_, i) => ({
							value: `${new Date().getFullYear() + i}`,
							label: `${new Date().getFullYear() + i}`
						}))
					]
				})
			]),

			// CVC
			new FormField({ name: "cvc", label: "CVC" }, [
				Input({
					type: "text",
					placeholder: "CVC",
					required: true,
					class: "border p-2 rounded-md w-full bg-background placeholder-muted-foreground text-foreground"
				})
			])
		]),

		// Submit button
		Button({
			type: "submit",
			class: "w-full mt-4 py-2 bg-primary text-primary-foreground rounded-md font-medium hover:bg-primary/90 transition"
		}, "Continue")
	])
);

/**
 * PaymentCard
 *
 * This component creates a card for adding a new payment method.
 *
 * @returns {object}
 */
export const PaymentCard = () => (
	Card({ class: "w-full max-w-md mx-auto p-8 bg-card space-y-6" }, [
		CardHeader({ title: "Payment Method", description: "Add a new payment method to your account." }),
		PaymentMethodSelector(),

		Div({ class: "space-y-4" }, [
			PaymentForm()
		])
	])
);

export default PaymentCard;