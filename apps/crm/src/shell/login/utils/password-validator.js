/**
 * PasswordValidator
 *
 * This will validate the password.
 *
 * @class PasswordValidator
 */
export class PasswordValidator
{
	/**
	 * @type {string}
	 * User's first name.
	 */
	#firstName;

	/**
	 * @type {string}
	 * User's last name.
	 */
	#lastName;

	/**
	 * @type {string}
	 * The password to validate.
	 */
	#password;

	/**
	 * @type {string[]}
	 * Array to hold validation error messages.
	 */
	#errors = [];

	/**
	 * The minimum required password length.
	 * @type {number}
	 */
	static MIN_LENGTH = 12;

	/**
	 * Creates a new PasswordValidator instance.
	 *
	 * @param {string} firstName - The user's first name.
	 * @param {string} lastName - The user's last name.
	 * @param {string} password - The password to validate.
	 */
	constructor(firstName, lastName, password)
	{
		this.#firstName = firstName;
		this.#lastName = lastName;
		this.#password = password;
	}

	/**
	 * Validates the password against various criteria.
	 *
	 * @returns {Object} An object containing:
	 *  - valid {boolean}: True if the password passes all validations.
	 *  - errors {string[]}: An array of error messages for each failed validation.
	 */
	validate()
	{
		// Reset errors for each validation run
		this.#errors = [];
		this.#validateLength();
		this.#validateUpper();
		this.#validateLower();
		this.#validateNumber();
		this.#validateSpecial();
		this.#validateName();

		return { valid: this.#errors.length === 0, errors: this.#errors };
	}

	/**
	 * Validates that the password meets the minimum length requirement.
	 *
	 * @returns {void}
	 */
	#validateLength()
	{
		if (this.#password.length < PasswordValidator.MIN_LENGTH)
		{
			this.#errors.push(
				`Password must be at least ${PasswordValidator.MIN_LENGTH} characters long.`
			);
		}
	}

	/**
	 * Validates that the password contains at least one uppercase letter.
	 *
	 * @returns {void}
	 */
	#validateUpper()
	{
		if (!/[A-Z]/.test(this.#password))
		{
			this.#errors.push("Password must contain at least one uppercase letter.");
		}
	}

	/**
	 * Validates that the password contains at least one lowercase letter.
	 *
	 * @returns {void}
	 */
	#validateLower()
	{
		if (!/[a-z]/.test(this.#password))
		{
			this.#errors.push("Password must contain at least one lowercase letter.");
		}
	}

	/**
	 * Validates that the password contains at least one numeric digit.
	 *
	 * @returns {void}
	 */
	#validateNumber()
	{
		if (!/\d/.test(this.#password))
		{
			this.#errors.push("Password must contain at least one digit.");
		}
	}

	/**
	 * Validates that the password contains at least one special character.
	 *
	 * @returns {void}
	 */
	#validateSpecial()
	{
		if (!/[^a-zA-Z0-9]/.test(this.#password))
		{
			this.#errors.push(
				"Password must contain at least one special character (e.g., !@#$%^&*())."
			);
		}
	}

	/**
	 * Validates that the password does not contain parts of the user's first or last name.
	 * It checks for any substring of length 3 from either the first name or last name in the password.
	 *
	 * @returns {void}
	 */
	#validateName()
	{
		const lowerPassword = this.#password.toLowerCase();
		const lowerFirstName = this.#firstName.toLowerCase();
		const lowerLastName = this.#lastName.toLowerCase();

		/**
		 * Checks if any substring of length 3 from the given name is contained within the password.
		 *
		 * @param {string} name - The name to check against the password.
		 * @returns {boolean} True if any substring is found in the password.
		 */
		const containsSubstring = (name) =>
		{
			for (let i = 0; i <= name.length - 3; i++)
			{
				const substring = name.substring(i, i + 3);
				if (lowerPassword.includes(substring))
				{
					return true;
				}
			}
			return false;
		};

		if (containsSubstring(lowerFirstName) || containsSubstring(lowerLastName))
		{
			this.#errors.push("Password must not contain parts of your name.");
		}
	}
}
