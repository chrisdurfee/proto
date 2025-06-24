import { Div } from "@base-framework/atoms";
import { BlankPage } from "@base-framework/ui/pages";
import { PageHeader } from "./page-header.js";
import { SummaryCards } from "./summary-cards.js";
import { TimeclockTable } from "./timeclock-table.js";
import { TIMES } from "./times.js";

/**
 * This will create the timeclock page.
 *
 * @returns {object}
 */
export const TimeclockPage = () => (
	new BlankPage([
		Div({ class: 'grid grid-cols-1' }, [
			Div({ class: 'flex flex-auto flex-col pt-0 lg:space-y-12 w-full mx-auto 2xl:max-w-[1600px]' }, [
				PageHeader(),
				Div({ class: 'flex flex-auto flex-col space-y-4 lg:space-y-2' }, [
					SummaryCards(),
					Div({ class: 'flex flex-col overflow-x-auto' }, [
						TimeclockTable({ times: TIMES })
					])
				])
			])
		])
	])
);

export default TimeclockPage;