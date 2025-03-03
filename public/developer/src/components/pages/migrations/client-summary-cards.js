import { Div } from "@base-framework/atoms";
import { Icons } from "@base-framework/ui/icons";
import { ClientSummaryCard } from "./client-summary-card.js";

/**
 * ClientSummaryCards
 *
 * A section displaying client summary cards.
 *
 * @returns {object}
 */
export const ClientSummaryCards = () => (
    Div({ class: 'hidden md:flex flex-auto overflow-x-auto -mx-6 px-6 pb-2' }, [
        Div({ class: 'inline-flex flex-auto space-x-4 ml-[-24px] pl-6' }, [
            ClientSummaryCard({
                title: 'Total Clients',
                value: '1,200',
                change: '+5.4% from last month',
                icon: Icons.user.group
            }),
            ClientSummaryCard({
                title: 'New Clients',
                value: '350',
                change: '+12% from last month',
                icon: Icons.user.plus
            }),
            ClientSummaryCard({
                title: 'Lost Clients',
                value: '25',
                change: '-3% from last month',
                icon: Icons.user.minus
            }),
            ClientSummaryCard({
                title: 'Total Revenue',
                value: '$145,678.00',
                change: '+10% from last month',
                icon: Icons.currency.dollar
            }),
        ])
    ])
);

export default ClientSummaryCards;