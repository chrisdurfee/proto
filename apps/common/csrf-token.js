import { base } from "@base-framework/base";

/**
 * Checks if the HTTP method is safe from CSRF protection.
 *
 * @param {string} method - The HTTP method to check.
 * @returns {boolean} - True if the method is safe, false otherwise.
 */
function csrfSafeMethod(method)
{
	return (/^(GET|HEAD|OPTIONS)$/.test(method));
}

/**
 * Sets the CSRF token for AJAX requests.
 *
 * @param {string} token - The CSRF token to set.
 * @returns {void}
 */
const setCsrfToken = (token) =>
{
	// @ts-ignore
	base.beforeSend((xhr, settings) =>
	{
		if (!csrfSafeMethod(settings.method) && !settings.crossDomain)
		{
			xhr.setRequestHeader('CSRF-TOKEN', token);
		}
	});
};

/**
 * This will setup the csrf token.
 *
 * @param {object} model - The model to get the CSRF token from.
 * @returns {void}
 */
export const getCsrfToken = (model) =>
{
	model.xhr.getCsrfToken('', (response) =>
	{
		if (!response || response.success === false)
		{
			return;
		}

		setCsrfToken(response.token);
	});
};