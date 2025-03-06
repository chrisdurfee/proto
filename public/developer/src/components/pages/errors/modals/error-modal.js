import { Code, Pre } from "@base-framework/atoms";
import { Data, DateTime } from "@base-framework/base";
import { Icons } from "@base-framework/ui/icons";
import { DetailBody, DetailSection, Modal, SplitRow } from "@base-framework/ui/molecules";

/**
 * Helper function to mimic the old displayResults() behavior.
 *
 * @param {string} message - The message to format.
 * @returns {string} - The formatted message.
 */
const displayResults = (message) =>
{
	try
	{
		const data = JSON.parse(message);
		return JSON.stringify(data, null, "\t");
	}
	catch (e)
	{
		return message;
	}
};

/**
 * Helper function to format code.
 *
 * @param {string} message - The message to format.
 * @returns {object}
 */
const FormatedCode = (message) => (
	Pre({ class: 'whitespace-break-spaces break-all cursor-pointer' }, [
		Code({ class: 'font-mono flex-auto text-sm text-wrap font-normal text-muted-foreground' }, message)
	])
);

/**
 * ErrorModal
 *
 * This modal displays error details using the new modal component.
 *
 * @param {object} props - The properties of the modal.
 * @returns {Modal} - A new instance of the Modal component.
 */
export const ErrorModal = (props) => new Modal(
{
	title: '[[title]]',
	description: 'Error Details',
	icon: Icons.bug,
	size: 'lg',
	type: 'right',
	hidePrimaryButton: true,
	error: props.error,

	/**
	 * Initializes the data store for the modal.
	 *
	 * @returns {Data} - A new Data instance.
	 */
	setData()
	{
		return new Data();
	},

	/**
	 * Lifecycle method before the modal setup.
	 *
	 * Processes the incoming error data and sets additional formatted properties.
	 */
	beforeSetup()
	{
		const error = this.error;
		this.data.set(
		{
			...error,
			//shorten the error message for title
			title: error.errorMessage.length > 20 ? error.errorMessage.substring(0, 20) + '...' : error.errorMessage,
			// Format the createdAt date, replacing space with 'T' and formatting if valid.
			formattedDate: error.createdAt
				? (error.createdAt.replace(' ', 'T') !== '0000-00-00T00:00:00'
					? DateTime.format('standard', error.createdAt.replace(' ', 'T')) + ' ' + DateTime.formatTime(error.createdAt.replace(' ', 'T'), 12)
					: '')
				: '',
			// Process JSON fields using the helper function.
			formattedQuery: displayResults(error.query),
			formattedErrorTrace: displayResults(error.errorTrace),
			formattedBackTrace: displayResults(error.backTrace)
		});
	},

	/**
	 * Lifecycle method triggered on modal close.
	 *
	 * Optional: Add cleanup or navigation logic here.
	 */
	onClose: () =>
	{
		// e.g., navigate away or perform cleanup.
	}
},
[
	// Modal content rendered using DetailBody and DetailSection with SplitRow components.
	DetailBody(
	[
		DetailSection({ title: 'Error Details' }, [
			SplitRow('Line Number', '[[errorLine]]'),
		]),
		DetailSection({ title: 'File' }, [
			FormatedCode('[[errorFile]]')
		]),
		DetailSection({ title: 'Message' }, [
			FormatedCode('[[errorMessage]]')
		]),
		DetailSection([
			SplitRow('IP Address', '[[errorIp]]'),
			SplitRow('Added', '[[formattedDate]]'),
		]),
		DetailSection([
			SplitRow('Url', '[[url]]'),
			SplitRow('Query', '[[formattedQuery]]'),
		]),
		DetailSection({ title: 'Stack Trace' }, [
			FormatedCode('[[formattedErrorTrace]]'),
		]),
		DetailSection({ title: 'Back Trace' }, [
			FormatedCode('[[formattedBackTrace]]')
		])
	])
]
);