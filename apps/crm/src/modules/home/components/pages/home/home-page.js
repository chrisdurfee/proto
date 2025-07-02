import { A, Code, Div, H1, H3, Header, P, Pre, Section } from '@base-framework/atoms';
import { Atom } from '@base-framework/base';
import { Button } from '@base-framework/ui/atoms';
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
		}, 'Get started'),
		A({
			href: 'https://github.com/chrisdurfee/base',
			target: '_blank',
			class: 'bttn ghost gap-2'
		}, 'Github')
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
				title: 'Build faster with Base and Tailwind CSS',
				description: 'Beautifully designed components using Tailwind CSS inspired by the genius at Shadcn and tailored to work with Base. Base is easy to use and open source.'
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
	Pre({ ...props, class: `flex p-4 max-h-[650px] max-w-[1024px] overflow-x-auto rounded-lg border bg-muted whitespace-break-spaces break-all cursor-pointer mt-4 ${props.class}` }, [
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
				title: 'Why Base?',
				description: 'Base is a small framework that does things like global and local states, routing, events, dates, data tracking, data binding, data watching, and rending of components using Atoms. It is designed to be simple and fast to build. It\'s open source and in npm.'
			}, [
				CodeCard('npm install @base-framework/base'),
				Div({ class: 'mt-10 flex items-center justify-start gap-x-4 pb-6' }, [
					A({
						href: 'https://github.com/chrisdurfee/base/wiki',
						target: '_blank',
						class: 'bttn ghost gap-2'
					}, 'Base Docs')
				])
			]),
			TextSection({
				title: 'Example App',
				description: 'This is an example Todo app built with base.'
			}, [
				CodeCard(`import { Button, Div, Form, H1, Input, Li, Ul } from "@base-framework/atoms";
import { Builder, Data } from "@base-framework/base";

/**
 * This will create a to-do app.
 *
 * @returns {object}
 */
export function ToDoApp()
{
    /**
     * This will set up the data store for the to-do app.
     */
    const data = new Data({ items: [] });

    /**
     * This will handle the form submission for adding a new to-do item.
     *
     * @param {object} event
     */
    const handleSubmit = (event) =>
    {
        event.preventDefault();
        const form = event.target;
        const input = form.querySelector('input');

        // add the new to-do item to the array of items
        data.push('items', input.value);
        input.value = '';
    };

    /**
     * This will handle removing a to-do item from the list.
     *
     * @param {number} index
     * @returns {boolean}
     */
    const handleRemove = (index) => data.splice('items', index);

    return Div([
        H1('To-Do App'),
        Form({ submit: handleSubmit }, [
            Input({ placeholder: 'Add a new item' }),
            Button({ type: 'submit' }, 'Add')
        ]),
        Ul({
            for: [data, 'items', (item, index) => Li([
                Span(item),
                Button({ click: () => handleRemove(index) }, 'Remove')
            ])]
        })
    ]);
}

/**
 * This will render the to-do app to the body of the document.
 */
Builder.render(ToDoApp(), document.body);`)
			]),

			TextSection({
				title: 'Sign Up, Onboarding, and Sign In',
			}, [
				P({ class: 'text-base text-muted-foreground py-2 max-w-[700px]' }, "This platform comes with the front end pages to sign up, onboard, and sign in. "),
				Div({ class: 'mt-10 flex items-center justify-start gap-x-4 pb-6' }, [
					A({
						href: './sign-up',
						target: '_blank',
						class: 'bttn ghost gap-2'
					}, 'Sign Up'),
					A({
						href: './onboarding',
						target: '_blank',
						class: 'bttn ghost gap-2'
					}, 'Onboarding'),
					Button({
						variant: 'ghost',
						click: () => app.signOut()
					}, 'Sign Out')
				])
			]),

			TextSection({
				title: 'Example Platform',
			}, [
				P({ class: 'text-base text-muted-foreground py-2 max-w-[700px]' }, "This app is an example of a platform that uses Base to render the app on the client-side. This responsive PWA (Progressive Web App) is installable and works on all devices. The app is built with Base and Tailwind CSS and is inspired by Shadcn UI. "),
				Div({ class: 'mt-10 flex items-center justify-start gap-x-4 pb-6' }, [
					A({
						href: 'https://ui.shadcn.com/',
						target: '_blank',
						class: 'bttn ghost gap-2'
					}, 'Shadcn UI')
				])
			]),

			TextSection({
				title: 'Vite Support',
			}, [
				P({ class: 'text-base text-muted-foreground py-2 max-w-[700px]' }, "This app is configured to use Vite for local development and production builds. Vite is a build tool that aims to provide a faster and leaner development experience for modern web projects. "),
			]),

			TextSection({
				title: 'Code Splitting',
			}, [
				P({ class: 'text-base text-muted-foreground py-2 max-w-[700px]' }, "Code splitting is supported to optimize performance and load only necessary code when required.")
			]),

			TextSection({
				title: 'Documentation',
			}, [
				P({ class: 'text-base text-muted-foreground py-2 max-w-[700px]' }, "Documentation is provided to assist developers in understanding and utilizing the framework, platform, and components effectively."),
				Div({ class: 'mt-10 flex items-center justify-start gap-x-4 pb-6' }, [
					A({
						href: '/docs',
						class: 'bttn primary'
					}, 'Learn more')
				])
			]),

			TextSection({
				title: 'Example Pages',
			}, [
				P({ class: 'text-base text-muted-foreground py-2 max-w-[700px]' }, "This app contains examples pages for the most common application needs. Calendar, Messaging, Dashboard, Lists, Grids, and more."),
			])
		])
	])
);

export default HomePage;