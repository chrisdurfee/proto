import { Div } from "@base-framework/atoms";
import { Card } from "@base-framework/ui/atoms";
import { DatePicker } from "@base-framework/ui/molecules";
import { CardHeader } from "./card-atoms.js";

/**
 * DateSelectCard Component
 *
 * This will create a date select card.
 *
 * @returns {object}
 */
export const DateSelectCard = ({ startDate }) => (
    Card({ class: "w-full max-w-md mx-auto p-6 bg-card space-y-6" }, [
        CardHeader({ title: "Pick a Date", description: "When is the event?" }),

         Div({ class: "font-medium text-foreground" }, [
            new DatePicker({
                selectedDate: startDate,
                selectedCallBack: (date) => console.log(date)
            })
         ])
    ])
);

export default DateSelectCard;