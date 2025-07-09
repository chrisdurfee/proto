import { Div } from "@base-framework/atoms";

/**
 * Helper function to create a page that uses dynamic imports.
 *
 * @param {string} uri The URI (route) for this page
 * @param {Function} importCallback A function returning the dynamic import
 * @returns {object}
 */
const Page = (uri, importCallback) => ({
	uri,
	import: importCallback,
});

/**
 * This will create a dockable page.
 *
 * @returns {object}
 */
const ContentPage = () => (
	Div({
		class: 'flex flex-auto flex-col p-4 lg:p-6',
		switch: [
			Page(`/users/:userId`, () => import('./profile/profile-page.js')),
			Page(`/users/:userId/login-times`, () => import('./login-times/login-time-page.js'))
		]
	})
);

/**
 * This will create the Content Section.
 *
 * @param {object} props
 * @returns {object}
 */
export const ContentSection = (props) => (
	Div({ class: 'flex flex-auto flex-col' }, [
		ContentPage()
	])
);