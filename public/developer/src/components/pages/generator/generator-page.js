import { Div } from "@base-framework/atoms";
import { BlankPage } from "@base-framework/ui/pages";
import { GeneratorCards } from "./generator-cards.js";
import { PageHeader } from "./page-header.js";

/**
 * This will create the generator page.
 *
 * @returns {object}
 */
export const GeneratorPage = () => (
    new BlankPage([
        Div({ class: 'grid grid-cols-1' }, [
            Div({ class: 'flex flex-auto flex-col p-6 pt-0 lg:space-y-12 md:pt-6 lg:p-8 w-full mx-auto lg:max-w-7xl' }, [
                PageHeader(),
                Div({ class: 'flex flex-auto flex-col space-y-4' }, [
                    GeneratorCards()
                ])
            ])
        ])
    ])
);

export default GeneratorPage;