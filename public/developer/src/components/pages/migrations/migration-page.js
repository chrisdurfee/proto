import { Div } from "@base-framework/atoms";
import { BlankPage } from "@base-framework/ui/pages";
import { clients } from "./clients.js"; // Import fake data
import { MigrationTable } from "./migration-table.js";
import { MigrationModel } from "./models/migration-model.js";
import { PageHeader } from "./page-header.js";

/**
 * This will create the migration list page.
 *
 * @returns {object}
 */
export const MigrationPage = () => (
    new BlankPage({

        /**
         * @type {object}
         */
        data: new MigrationModel(),

        /**
         * This will fetch the data for the page.
         *
         * @param {number} offset
         * @param {number} limit
         * @param {function} callback
         * @returns {void}
         */
        fetch(offset, limit, callback)
        {
            this.data.xhr.all(offset, limit, (response) =>
            {
                if (response)
                {
                    callback(response.rows);
                }
            });
        }
    }, [
        Div({ class: 'grid grid-cols-1' }, [
            Div({ class: 'flex flex-auto flex-col p-6 pt-0 lg:space-y-12 md:pt-6 lg:p-8 w-full mx-auto lg:max-w-7xl' }, [
                PageHeader(),
                Div({ class: 'flex flex-auto flex-col space-y-4 lg:space-y-2' }, [
                    Div({ class: 'flex flex-col overflow-x-auto' }, [
                        MigrationTable({ clients })
                    ])
                ])
            ])
        ])
    ])
);

export default MigrationPage;