import { A, Code, Div, H1, H3, Header, P, Pre, Section } from '@base-framework/atoms';
import { Atom } from '@base-framework/base';
import { Icons } from "@base-framework/ui/icons";
import { BlankPage } from "@base-framework/ui/pages";

/**
 * This will create a main button container.
 *
 * @returns {object}
 */
const MainButtonContainer = Atom(() => (
    Div({ class: 'mt-10 flex items-center justify-start gap-x-4 px-6 pb-6 border-b' }, [
		A({
			href: '/docs',
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
 * This will create a text section.
 *
 * @param {object} props
 * @returns {object}
 */
const TextSection = Atom(({ title, description }, children) => (
	Section({ class: 'flex flex-col w-full px-6 py-8 border-b' }, [
		SectionHeader({
			title,
			description
		}),
		children
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
			class: 'flex flex-auto flex-col w-full max-w-[1400px] m-auto sm:pt-8 lg:border-r lg:border-l'
		}, [
			PageHeader({
				title: 'Build faster with Proto and Base',
				description: 'Proto is a well designed framework that provides a set of tools and utilities to help you build applications faster and more efficiently.'
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
			TextSection({
				title: 'Why Proto?',
				description: 'Proto is built to simplify the development process. It\'s modular, supports small and large teams, and has many of the tools needed out of the box. It\'s also open source.'
			}, [
				CodeCard('composer install protoframework/proto'),
				Div({ class: 'mt-10 flex items-center justify-start gap-x-4 pb-6' }, [
					A({
						href: 'https://github.com/chrisdurfee/proto',
						target: '_blank',
						class: 'bttn ghost gap-2'
					}, 'Proto Source')
				])
			]),
			TextSection({
				title: 'Security',
				description: 'Proto comes with built-in security features to help you protect your application. It includes support for authentication, authorization, and data protection. Gates, policies, and middleware are all included to help you secure your application.'
			}),
			TextSection({
				title: 'Code Generation',
				description: 'Proto comes with built-in code generation features to help you quickly scaffold your application. It includes support for generating full modules, gateways, controllers, models, and more.'
			}, [
				Div({ class: 'mt-10 flex items-center justify-start gap-x-4 pb-6' }, [
					A({
						href: '/generator',
						class: 'bttn ghost gap-2'
					}, 'Use Generator')
				])
			]),
			TextSection({
				title: 'Error Tracking',
				description: 'Proto has built-in error tracking features to help you monitor and fix issues in your application. It includes support for logging, monitoring, and alerting.'
			}, [
				Div({ class: 'mt-10 flex items-center justify-start gap-x-4 pb-6' }, [
					A({
						href: '/errors',
						class: 'bttn ghost gap-2'
					}, 'View Errors')
				])
			]),
		])
	])
);

export default HomePage;