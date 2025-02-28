import { Button, Div, P } from '@base-framework/atoms';
import { Atom, Component } from '@base-framework/base';

/**
 * This will create a button group.
 *
 * @param {object} props
 * @param {array} children
 * @returns {object}
 */
const Buttons = Atom((props, children) => ({
	class: 'flex items-center px-4 py-2',
	...props,
	children
}));

/**
 * This will create a state button.
 *
 * @param {object} props
 * @returns {object}
 */
const StateButton = Atom(({ value, label }) => (
	Button({
		class: 'inline-flex flex-auto items-center justify-center whitespace-nowrap rounded-md px-8 py-1 text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow',
		onState: ['performance', { active: value}],
		dataSet: ['performance', ['state', value, 'active']],
		click: (e, {state}) => state.performance = value
	}, label)
));

/**
 * This is a map of button text.
 *
 * @type {object}
 * @const
 */
const ButtonText =
{
	fair: 'This is just Fair',
	good: 'So Good!',
	bad: 'This is bad'
};

/**
 * ButtonGroup
 *
 * This will create a button group.
 *
 * @class
 * @extends Component
 */
export default class ButtonGroup extends Component
{
    /**
     * This will render the component.
     *
     * @returns {object}
     */
	render()
	{
		return Buttons({ class: 'flex flex-auto flex-col' }, [
			P({
				onState: ['performance', (state) => ButtonText[state] || ButtonText.fair]
			}),
			Div({ class: 'inline-flex h-9 items-center justify-center rounded-lg bg-muted p-1 text-muted-foreground'}, [
				StateButton({ label: 'Bad', value: 'bad' }),
				StateButton({ label: 'Good', value: 'good' }),
				StateButton({ label: 'Fair', value: 'fair' })
			])
		]);
	}

    /**
     * This will setup the states.
     *
     * @returns {object}
     */
	setupStates()
	{
		return {
			performance: 'fair'
		};
	}
}