import { Div, H1, P } from '@base-framework/atoms';
import { Icon } from '@base-framework/ui/atoms';
import { Icons } from '@base-framework/ui/icons';
import { StepContainer } from '../atoms/step-container';

/**
 * @function CongratulationsSection
 * @description
 *  A page section that displays the final success message.
 *
 * @returns {object} A Section component containing the success message UI.
 */
export const CongratulationsSection = () => (
	StepContainer([
		Div({ class: 'flex flex-col items-start' }, [
			Div({ class: 'w-16 h-16 mb-6 text-primary' }, [
				Icon(Icons.circleCheck)
			]),
			H1({ class: 'text-2xl font-bold mb-4' }, "You're Ready to Build!"),
			P({ class: 'text-base text-muted-foreground mb-4' },
				"You now have a solid understanding of the App Shell framework. Start building your application with confidence."
			)
		])
	])
);