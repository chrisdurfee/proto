import { Div } from "@base-framework/atoms";
import { BlankPage } from "@base-framework/ui/pages";
import { ErrorTable } from "./error-table.js";
import { PageHeader } from "./page-header.js";

/**
 * @type {object}
 */
const Props =
{
    /**
     * This will update the error table when the url is
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
 * This will create the error page.
 *
 * @returns {object}
 */
export const ErrorPage = () => (
    new BlankPage(Props, [
        Div({ class: 'grid grid-cols-1' }, [
            Div({ class: 'flex flex-auto flex-col p-6 pt-0 space-y-6 md:space-y-12 md:pt-6 lg:p-8 w-full mx-auto lg:max-w-7xl' }, [
                PageHeader(),
                Div({ class: 'flex flex-auto flex-col space-y-2 md:space-y-4' }, [
                    Div({ class: 'flex flex-col overflow-x-auto' }, [
                        ErrorTable()
                    ])
                ])
            ])
        ])
    ])
);

export default ErrorPage;