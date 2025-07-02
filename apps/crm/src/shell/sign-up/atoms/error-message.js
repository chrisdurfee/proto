import { Div, Form, H2, Header, P } from '@base-framework/atoms';
import { Atom } from '@base-framework/base';
import { Button } from "@base-framework/ui/atoms";

/**
 * @function ErrorMessage
 * @description
 *  Displays an error message with a button to return to the welcome step.
 *
 * @returns {object} A Div component containing the error message.
 */
export const ErrorMessage = Atom(() =>
(
	Div({ class: 'w-full max-w-sm bg-card text-card-foreground shadow rounded-xl sm:border sm:shadow-lg p-6' }, [
        Form({ class: 'flex flex-auto flex-col' }, [
            Div({ class: 'flex flex-auto flex-col space-y-4' }, [
                Header({ class: 'py-4 text-center' }, [
                    H2({ class: 'text-xl font-bold' }, 'Error Occurred!'),
                    P('There was an error creating your account. Please try again.'),
                ]),
                Button({
                    variant: 'primary',
                    click: () => app.navigate('/sign-up')
                }, 'Try Again')
            ])
        ])
	])
));