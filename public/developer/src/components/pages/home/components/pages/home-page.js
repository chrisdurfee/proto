import { A, Code, Div, H1, H3, Header, P, Pre } from '@base-framework/atoms';
import { Atom } from '@base-framework/base';
import { Icons } from "@base-framework/ui/icons";
import { BlankPage } from "@base-framework/ui/pages";

/**
 * This will create a main button container.
 *
 * @returns {object}
 */
const MainButtonContainer = Atom(() => (
    Div({ class: 'mt-10 flex items-center justify-start gap-x-4 px-6 pb-6' }, [
		A({
			href: '/department',
			class: 'bttn primary'
		}, 'Get started')
	])
));

/**
 * This will create a header for the documentation.
 *
 * @param {object} props
 * @returns {object}
 */
const PageHeader = Atom(({ title, description}) => (
	Header({ class: 'flex flex-col px-6' }, [
		H1({ class: 'text-3xl font-bold leading-tight tracking-tighter md:text-4xl lg:leading-[1.1]' }, title),
		description && P({ class: 'max-w-2xl text-lg font-light text-foreground mt-2' }, description),
	])
));

/**
 * This will create a header for the documentation.
 *
 * @param {object} props
 * @returns {object}
 */
export const SectionHeader = Atom(({ title, description}) => (
	Header({ class: 'flex flex-col' }, [
		H3({ class: 'scroll-m-20 text-2xl font-bold tracking-tight' }, title),
		description && P({ class: 'text-base text-muted-foreground py-2 max-w-[700px]' }, description),
	])
));

/**
 * This will create a main section.
 *
 * @param {object} props
 * @returns {object}
 */
const MainSection = Atom((props, children) => (
	Div({ class: 'flex flex-auto flex-col' }, [
		Div({
			class: 'flex flex-auto flex-col w-full max-w-[1400px] m-auto sm:pt-8'
		}, [
			PageHeader({
				title: 'Build faster together',
				description: 'Welcome to our Team. We are excited to have you here. This application can be used as a resource to learn and share about our team.'
			}),
			MainButtonContainer(),
			...children
		])
	])
));

/**
 * This will create a preview card.
 *
 * @param {object} props
 * @param {object} children
 * @returns {object}
 */
const CodeCard = Atom((props, children) => (
	Pre({ ...props, class: `flex p-4 max-h-[650px] max-w-[1024px] overflow-x-auto rounded-lg bg-muted whitespace-break-spaces break-all cursor-pointer mt-4 ${props.class}` }, [
        Code({ class: 'font-mono flex-auto text-sm text-wrap', click: () =>
        {
            navigator.clipboard.writeText(children[0].textContent);

			// @ts-ignore
            app.notify({
                title: "Code copied",
                description: "The code has been copied to your clipboard.",
                icon: Icons.clipboard.checked
            });
        }}, children)
    ])
));

/**
 * HomePage
 *
 * This will create a home page.
 *
 * @returns {BlankPage}
 */
export const HomePage = () => (
	new BlankPage([
		MainSection([

		])
	])
);

export default HomePage;