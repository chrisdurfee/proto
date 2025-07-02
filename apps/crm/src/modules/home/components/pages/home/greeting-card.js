import { Div } from "@base-framework/atoms";
import { DateTime } from "@base-framework/base";
import { DynamicTime } from "@base-framework/organisms";

/**
 * This will get the current time.
 *
 * @returns {string}
 */
const getCurrentTime = () => DateTime.formatTime('', 24);

/**
 * This will get the greeting message.
 *
 * @returns {string}
 */
const getGreetingMessage = () =>
{
	let message = 'Morning';

	const current = getCurrentTime();
	if (current >= '18:00:00')
	{
		message = 'Evening';
	}
	else if (current >= '12:00:00')
	{
		message = 'Afternoon';
	}

	return `Good ${message}`;
};

/**
 * This will create a greeting card.
 *
 * @returns {object}
 */
export const GreetingCard = () => (
	Div({ class: 'flex flex-col rounded-lg border bg-card text-base text-muted-foreground shadow-sm my-4 mx-5 p-4' }, [
		new DynamicTime({
			filter: () => getGreetingMessage()
		})
	])
);