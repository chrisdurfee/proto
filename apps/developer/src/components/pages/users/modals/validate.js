import { Icons } from "@base-framework/ui/icons";
import { PasswordValidator } from "../../../../../../common/utils/password/password-validator.js";

/**
 * Validates the password and confirm password.
 *
 * @param {string} password - The password to validate.
 * @param {string} confirmPassword - The password to compare against.
 * @returns {boolean}
 */
export const validate = (password = '', confirmPassword = '') =>
{
	if (password !== confirmPassword)
	{
		app.notify({
			title: 'Error',
			description: 'Passwords do not match.',
			type: 'destructive',
			icon: Icons.shield
		});
		return false;
	}

	const firstName = '';
	const lastName = '';
	const validator = new PasswordValidator(firstName, lastName, password);
	const result = validator.validate();
	if (result.valid)
	{
		return true;
	}

	app.notify({
		icon: Icons.shield,
		title: 'Error',
		description: 'Password does not meet requirements.',
		type: 'warning'
	});
	return false;
};