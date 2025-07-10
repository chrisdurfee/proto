import { Div } from '@base-framework/atoms';
import { ChangePasswordSection } from './sections/change-password/change-password-section.js';

/**
 * This will create the data for the change password container.
 *
 * @returns {object}
 */
export const ChangePasswordContainer = () => (
	Div({ class: 'flex flex-auto flex-col' }, [
		ChangePasswordSection()
	])
);

export default ChangePasswordContainer;