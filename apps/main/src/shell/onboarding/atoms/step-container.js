import { Div } from '@base-framework/atoms';
import { Atom } from '@base-framework/base';
import { Skeleton } from '@base-framework/ui/atoms';

/**
 * This will create a left nav step.
 *
 * @param {object} props
 * @param {array} children
 * @returns {Array<object>}
 */
const LeftNavStep = (props, children) => ([
	Div({ class: 'w-full md:max-w-[400px] p-4' }, children),
	Div({ class: 'hidden md:flex flex-auto' }, [
		Skeleton({ width: "w-full", height: "h-full", class: "rounded-lg" })
	])
]);

/**
 * This will create a right nav step.
 *
 * @param {object} props
 * @param {array} children
 * @returns {Array<object>}
 */
const RightNavStep = (props, children) => ([
	Div({ class: 'hidden md:flex flex-auto' }, [
		Skeleton({ width: "w-full", height: "h-full", class: "rounded-lg" })
	]),
	Div({ class: 'w-full md:max-w-[400px] p-4' }, children),
]);

/**
 * This will create a step container.
 *
 * @param {object} props
 * @param {array} children
 * @returns {object}
 */
export const StepContainer = Atom((props, children) => (
	Div({ class: 'flex flex-auto flex-col md:flex-row fadeIn' },
		(props?.variant === 'right') ? RightNavStep(props, children) : LeftNavStep(props, children)
	)
));