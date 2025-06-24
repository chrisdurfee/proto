import { Div } from "@base-framework/atoms";
import { Icons } from "@base-framework/ui/icons";
import { SummaryCard } from "./summary-card.js";
import { TIMES } from "./times.js";

/**
 * Calculates summary statistics from timeclock data.
 *
 * @returns {object} Object containing calculated statistics
 */
const calculateSummaryStats = () =>
{
	// Get today's date
	const today = new Date();
	const todayStr = today.toISOString().split('T')[0];

	// Calculate one month ago
	const lastMonth = new Date(today);
	lastMonth.setMonth(today.getMonth() - 1);
	const lastMonthStr = lastMonth.toISOString().split('T')[0];

	// Filter for active employees
	const activeEmployees = new Set();
	TIMES.forEach(entry =>
	{
		if (entry.status === 'Active')
		{
			activeEmployees.add(entry.employeeName);
		}
	});

	// Calculate total hours
	const totalHours = TIMES.reduce((sum, entry) => sum + entry.hoursWorked, 0);

	// Calculate hours for today
	const todaysEntries = TIMES.filter(entry => entry.date === todayStr);
	const todaysHours = todaysEntries.reduce((sum, entry) => sum + entry.hoursWorked, 0);

	// Calculate labor cost
	const laborCost = TIMES.reduce((sum, entry) =>
	{
		return sum + (entry.hoursWorked * entry.hourlyRate);
	}, 0);

	// Find average hourly rate
	const avgRate = TIMES.reduce((sum, entry) => sum + entry.hourlyRate, 0) / TIMES.length;

	return {
		activeCount: activeEmployees.size,
		totalHours: totalHours.toFixed(1),
		todaysHours: todaysHours.toFixed(1),
		laborCost: laborCost.toFixed(2),
		avgRate: avgRate.toFixed(2)
	};
};

/**
 * SummaryCards
 *
 * A section displaying summary cards for timeclock data.
 *
 * @returns {object}
 */
export const SummaryCards = () =>
{
	const stats = calculateSummaryStats();

	return Div({ class: 'hidden md:flex flex-auto overflow-x-auto -mx-6 px-6 pb-2' }, [
		Div({ class: 'inline-flex flex-auto space-x-4 ml-[-24px] pl-6' }, [
			SummaryCard({
				title: 'Active Employees',
				value: stats.activeCount.toString(),
				change: 'Currently clocked in',
				icon: Icons.user.group
			}),
			SummaryCard({
				title: 'Total Hours',
				value: stats.totalHours,
				change: 'Last two weeks',
				icon: Icons.clock
			}),
			SummaryCard({
				title: 'Today\'s Hours',
				value: stats.todaysHours,
				change: 'Hours worked today',
				icon: Icons.calendar.default
			}),
			SummaryCard({
				title: 'Labor Cost',
				value: `$${stats.laborCost}`,
				change: `Avg rate: $${stats.avgRate}/hr`,
				icon: Icons.currency.dollar
			}),
		])
	]);
};

export default SummaryCards;