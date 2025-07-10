import { Form } from '@base-framework/atoms';
import { Atom } from '@base-framework/base';
import { DatePicker, FormField } from '@base-framework/ui';
import { Button, Fieldset, Input } from "@base-framework/ui/atoms";
import { STEPS } from '../steps.js';

/**
 * @function UserDetailsForm
 * @description
 *  Renders a simple form to collect user details.
 *
 * @returns {object} A Div component containing the user details form.
 */
export const UserDetailsForm = Atom(() =>
(
	Form({
			submit: (e, parent) =>
			{
				e.preventDefault();
				parent.showStep(STEPS.CONGRATULATIONS);
			}
		}, [
		Fieldset({ legend: 'Profile' }, [
			new FormField({
				name: "fullname",
				label: "Full Name",
				description: "This is your public display name."
			}, [
				Input({ placeholder: "e.g. Jane Doe", required: true })
			]),

			new FormField({
				name: "birthday",
				label: "Birthday",
				description: "Please enter your date of birth."
			}, [
				new DatePicker({ required: true })
			]),

			new FormField({
				name: "password",
				label: "Password",
				description: "Keep it secret, keep it safe."
			}, [
				Input({ type: "password", placeholder: "********", required: true })
			]),

			Button({ type: "submit" }, "Submit")
		])
	])
));