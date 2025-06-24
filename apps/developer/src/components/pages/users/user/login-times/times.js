/**
 * Mock timeclock data.
 *
 * @typedef {Object} TimeEntry
 * @property {number} id - The unique identifier for the time entry
 * @property {string} employeeName - The name of the employee
 * @property {string} employeeImage - URL to employee's avatar image
 * @property {string} email - Employee's email address
 * @property {string} date - The date of the time entry
 * @property {string} clockIn - Time the employee clocked in
 * @property {string} clockOut - Time the employee clocked out (if applicable)
 * @property {number} hoursWorked - Total hours worked
 * @property {string} status - Current status of the entry (Complete/Incomplete)
 * @property {string} department - Employee's department
 * @property {number} hourlyRate - Employee's hourly rate
 *
 * @type {Array<TimeEntry>}
 */
export const TIMES = Array.from({ length: 20 }, (_, i) =>
{
	const id = i + 1;
	const employeeId = Math.floor(Math.random() * 10) + 1; // 1-10 employees
	const employeeName = [
		"John Smith",
		"Maria Rodriguez",
		"David Johnson",
		"Sarah Williams",
		"Michael Brown",
		"Jennifer Davis",
		"Robert Miller",
		"Lisa Garcia",
		"James Wilson",
		"Emily Martinez"
	][employeeId - 1];

	// Generate a date within the past 14 days
	const today = new Date();
	const daysAgo = Math.floor(Math.random() * 14);
	const entryDate = new Date(today);
	entryDate.setDate(today.getDate() - daysAgo);

	// Format date as YYYY-MM-DD
	const date = entryDate.toISOString().split('T')[0];

	// Random clock in time between 7 AM and 10 AM
	const clockInHour = Math.floor(Math.random() * 3) + 7;
	const clockInMinute = Math.floor(Math.random() * 60);
	const clockIn = `${clockInHour.toString().padStart(2, '0')}:${clockInMinute.toString().padStart(2, '0')} AM`;

	// Determine if the entry is complete
	const isComplete = Math.random() > 0.2; // 80% chance of being complete

	// If complete, set clock out time between 3 PM and 7 PM
	let clockOut = "";
	let hoursWorked = 0;

	if (isComplete)
	{
		const clockOutHour = Math.floor(Math.random() * 5) + 3;
		const clockOutMinute = Math.floor(Math.random() * 60);
		clockOut = `${clockOutHour.toString().padStart(2, '0')}:${clockOutMinute.toString().padStart(2, '0')} PM`;

		// Calculate hours worked (approximate)
		hoursWorked = (clockOutHour + 12) - clockInHour - (clockOutMinute < clockInMinute ? 0.5 : 0);
	}

	// Departments
	const departments = ["Engineering", "Sales", "Marketing", "Support", "HR", "Finance"];
	const department = departments[employeeId % departments.length];

	// Hourly rate based on department
	const baseRate = 20;
	const departmentMultiplier = {
		"Engineering": 1.5,
		"Sales": 1.3,
		"Marketing": 1.2,
		"Support": 1.0,
		"HR": 1.1,
		"Finance": 1.4
	};
	const hourlyRate = baseRate * (departmentMultiplier[department] || 1);

	return {
		id,
		employeeName,
		employeeImage: `https://i.pravatar.cc/150?img=${(employeeId % 70) + 1}`,
		email: `${employeeName.toLowerCase().replace(' ', '.')}@example.com`,
		date,
		clockIn,
		clockOut: isComplete ? clockOut : "Active",
		hoursWorked: isComplete ? hoursWorked : (new Date().getHours() - clockInHour),
		status: isComplete ? "Complete" : "Active",
		department,
		hourlyRate
	};
});