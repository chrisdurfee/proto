import { Div } from "@base-framework/atoms";
import { BlankPage } from "@base-framework/ui/pages";
import { MigrationTable } from "./migration-table.js";
import { PageHeader } from "./page-header.js";

/**
 * This will create the migration list page.
 *
 * @returns {object}
 */
export const MigrationPage = () => (
    new BlankPage([
        Div({ class: 'grid grid-cols-1' }, [
            Div({ class: 'flex flex-auto flex-col p-6 pt-0 space-y-12 md:pt-6 lg:p-8 w-full mx-auto lg:max-w-7xl' }, [
                PageHeader(),
                Div({ class: 'flex flex-auto flex-col space-y-4' }, [
                    Div({ class: 'flex flex-col overflow-x-auto' }, [
                        MigrationTable({
                            rows: [
                                {
                                    id: 1,
                                    createdAt: '2023-10-01',
                                    migration: 'Initial Migration',
                                    groupId: 'Group 1',
                                }
                            ]
                        })
                    ])
                ])
            ])
        ])
    ])
);

export default MigrationPage;