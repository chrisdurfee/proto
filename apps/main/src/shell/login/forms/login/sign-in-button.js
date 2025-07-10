import { Div, OnState } from '@base-framework/atoms';
import { Button, LoadingButton } from '@base-framework/ui/atoms';

/**
 * This will create the sign in button.
 *
 * @returns {object}
 */
export const SignInButton = () => (
	Div({ class: 'grid gap-4' }, [
		OnState('loading', (state) => (state)
			? LoadingButton({ disabled: true })
			: Button({ type: 'submit' }, 'Login')
		)
	])
);