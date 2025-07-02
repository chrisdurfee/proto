import { Div } from '@base-framework/atoms';
import { Component, Data, Jot } from '@base-framework/base';
import { ChangePasswordSection } from './sections/change-password-section.js';

/**
 * This will create the data for the change password container.
 *
 * @type {typeof Component}
 */
export const ChangePasswordContainer = Jot(
{
	/**
	 * This will setup the data.
	 *
	 * @returns {object}
	 */
	setData()
	{
		return new Data({
			password: '',
			confirmPassword: ''
		});
	},

	/**
	 * This will render the change password container.
	 *
	 * @returns {object}
	 */
	render()
	{
		return Div({ class: 'flex flex-auto flex-col' }, [
			ChangePasswordSection()
		]);
	}
});

export default ChangePasswordContainer;