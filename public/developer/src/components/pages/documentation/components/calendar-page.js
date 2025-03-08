import { DateTime } from "@base-framework/base";
import { DynamicTime } from "@base-framework/organisms";
import { TimeFrame } from "@base-framework/ui/molecules";
import { Calendar, WeekCalendar } from "@base-framework/ui/organisms";
import { DocSection } from "../../../molecules/doc-section.js";
import { DocPage } from '../../doc-page.js';

/**
 * This will get the current time.
 *
 * @returns {string}
 */
const getCurrentTime = () => DateTime.formatTime('', 24);

/**
 * CalendarPage
 *
 * This will create a calendar page.
 *
 * @returns {DocPage}
 */
export const CalendarPage = () => (
	DocPage(
        {
            title: 'Calendars',
            description: 'This is the calendar component documentation.',
        },
        [
            DocSection({
                title: 'Usage',
                description: 'This is how you can use the calendar component.',
                preview: [
                    new Calendar()
                ],
                code: `@base-framework/ui/organisms;

new Calendar()`
            }),

            DocSection({
                title: 'Week Calendar',
                description: 'This is how you can use the week calendar component.',
                preview: [
                    new WeekCalendar()
                ],
                code: `@base-framework/ui/organisms;

new Calendar()`
            }),

            DocSection({
                title: 'Dynamic Time',
                description: 'The Dynamic Time class will allow you to create a dynamic time that will update every minute.',
                preview: [
                    new DynamicTime({
                        filter: () =>
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
                        }
                    })
                ],
                code: `import { DateTime } from "@base-framework/base";
import { DynamicTime } from "@base-framework/organisms";

new DynamicTime({
    filter: () =>
    {
        let message = 'Morning';

        const current = DateTime.formatTime('', 24);
        if (current >= '18:00:00')
        {
            message = 'Evening';
        }
        else if (current >= '12:00:00')
        {
            message = 'Afternoon';
        }

        return \`Good \${message}\`;
    }
})`
            }),

            DocSection({
                title: 'Dynamic Local Time by Time Frame',
                description: 'This will show the time frame based on the local time. This will update the time frame every minute to keep the time frame updated.',
                preview: [
                    TimeFrame({
                        dateTime: '2024-11-04T18:00:00',
                        remoteTimeZone: 'America/Denver'
                    })
                ],
                code: `import {  TimeFrame  } from "@base-framework/ui/molecules";

TimeFrame({
    dateTime: '2024-11-04T18:00:00',
    remoteTimeZone: 'America/Denver'
})`
            })
        ]
    )
);

export default CalendarPage;