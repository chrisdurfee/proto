/**
 * Get the start or end date of the current month.
 *
 * @param {string} type - The type of date to get ('start' or 'end').
 * @returns {string} - The start or end date of the current month.
 */
export const getDate = (type = 'start') =>
{
	const date = new Date();
	// Set the date to the start or end of the month
	if (type === 'start')
	{
		date.setDate(1);
		date.setHours(0, 0, 0, 0);
	}
	else
	{
		date.setMonth(date.getMonth() + 1);
		date.setDate(0);
	}

	return date.toISOString().slice(0, 10);
};