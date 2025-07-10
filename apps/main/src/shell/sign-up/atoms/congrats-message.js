import { Div, Form, H2, Header, P } from '@base-framework/atoms';
import { Atom } from '@base-framework/base';
import { Button, Icon } from "@base-framework/ui/atoms";
import { Icons } from '@base-framework/ui/icons';

/**
 * @function CongratsMessage
 * @description
 *  Displays a final success message with a button to go to the app.
 *
 * @returns {object} A Div component containing the congratulations message.
 */
export const CongratsMessage = Atom(() =>
(
	Div({ class: 'w-full max-w-sm bg-card text-card-foreground shadow rounded-xl sm:border sm:shadow-lg p-6' }, [
        Form({ class: 'flex flex-auto flex-col' }, [
            Div({ class: 'flex flex-auto flex-col space-y-4' }, [
                Div({ class: 'flex flex-auto items-center justify-center' }, [
                    Div({ class: 'w-16 h-16 mb-6 text-primary' }, [
                        Icon(Icons.circleCheck)
                    ])
                ]),
                Header({ class: 'py-4 text-center' }, [
                    H2({ class: 'text-xl font-bold' }, 'Congratulations!'),
                    P('Your account has been created successfully.'),
                ]),
                Button({
                    variant: 'primary',
                    click: () => app.navigate('/onboarding')
                }, 'Let\'s go!')
            ])
        ])
	])
));