import { Div } from "@base-framework/atoms";
import { Icons } from "@base-framework/ui/icons";
import { ResourceCard } from "./resource-card.js";

/**
 * GeneratorCards
 *
 * A section displaying generator cards.
 *
 * @returns {object}
 */
export const GeneratorCards = () => (
    Div({ class: 'hidden md:flex flex-auto overflow-x-auto -mx-6 px-6 pb-2' }, [
        Div({ class: 'flex flex-auto space-x-4 ml-[-24px] pl-6' }, [
            ResourceCard({
                title: 'Total Clients',
                value: '1,200',
                change: '+5.4% from last month',
                icon: Icons.user.group
            }),
            ResourceCard({
                title: 'New Clients',
                value: '350',
                change: '+12% from last month',
                icon: Icons.user.plus
            }),
            ResourceCard({
                title: 'Lost Clients',
                value: '25',
                change: '-3% from last month',
                icon: Icons.user.minus
            }),
            ResourceCard({
                title: 'Total Revenue',
                value: '$145,678.00',
                change: '+10% from last month',
                icon: Icons.currency.dollar
            }),
        ])
    ])
);

export default GeneratorCards;