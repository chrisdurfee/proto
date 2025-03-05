import { Div } from "@base-framework/atoms";
import { BlankPage } from "@base-framework/ui/pages";
import { MigrationTable } from "./migration-table.js";
import { PageHeader } from "./page-header.js";

/**
 * @type {object}
 */
const Props =
{
    /**
     * This will update the migration list when the url is
     * updated.
     *
     * @returns {void}
     */
    update()
    {
        if (this.list)
        {
            this.list.refresh();
        }
    }
}

/**
 * This will create the migration list page.
 *
 * @returns {object}
 */
export const MigrationPage = () => (
    new BlankPage(Props, [
        Div({ class: 'grid grid-cols-1' }, [
            Div({ class: 'flex flex-auto flex-col p-6 pt-0 space-y-12 md:pt-6 lg:p-8 w-full mx-auto lg:max-w-7xl' }, [
                PageHeader(),
                Div({ class: 'flex flex-auto flex-col space-y-4' }, [
                    Div({ class: 'flex flex-col overflow-x-auto' }, [
                        MigrationTable()
                    ])
                ])
            ])
        ])
    ])
);

export default MigrationPage;