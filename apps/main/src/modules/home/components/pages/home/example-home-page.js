import { Div, H2, P } from "@base-framework/atoms";
import { Data } from "@base-framework/base";
import { Button } from "@base-framework/ui/atoms";
import { GridContainer } from "@base-framework/ui/molecules";
import { Calendar } from "@base-framework/ui/organisms";
import { FullPage } from "@base-framework/ui/pages";
import { GreetingCard } from './greeting-card.js';

/**
 * This will get the data for the test.
 *
 * @returns {object}
 */
const getData = () => ({
	name: 'name',
	class: 'active',
	other: {
		name: 'name',
		class: 'active'
	},
	items: [
		{
			name: 'name',
		}
	]
});

/**
 * This will test the deep data binding [[other.name]]
 *
 * @param {object} data
 * @returns {void}
 */
const testData = (data) =>
{
	// test setter
	data.set('name', 'Home');

	const DURATION = 5000; // five seconds

	/**
	 * This will test the deep data binding [[other.name]]
	 * for five seconds to see if it updates.
	 */
	setTimeout(() =>
	{
		// test first level
		data.name = 'Update';
		data.class = 'inactive';

		// test deep level
		data.other.name = 'test';
		data.other.class = 'inactive';

		// test array level
		data.items[0].name = 'test';
		data.items[0].class = 'inactive';
		console.log(data)
	}, DURATION);
};

/**
 * 	This will set up the component props.
 *
 * @returns {object}
 */
const FullProps = () => (
{
	/**
	 * @member {string} title
	 */
	title: 'Main Page [[name]]',

	/**
	 * @member {string} title
	 */
	description: 'This is the main page.',

	/**
	 * This will add the data to the component.
	 *
	 * @override
	 * @returns {Data}
	 */
	setData()
	{
		const settings = getData();
		return new Data(settings);
	},

	/**
	 * This will run after the setup.
	 *
	 * @override
	 * @returns {void}
	 */
	afterSetup()
	{
		const data = this.data;
		testData(data);
	}
});

/**
 * This will create a bind card.
 *
 * @returns {object}
 */
const BindCard = () => (
	Div({ class: 'flex flex-col rounded-lg border bg-card text-card-foreground shadow-sm my-4 mx-5 p-4' }, [
		H2({ cache: 'header', class: 'scroll-m-20 text-2xl font-bold tracking-tight' }, 'Binding Test'),
		P({ class: 'my-2' }, 'This will test the deep data binding [[other.name]]'),
		new Calendar()
	])
);

/**
 * This will create a sign out card.
 *
 * @returns {object}
 */
const SignOutCard = () => (
	Div({ class: 'flex flex-col rounded-lg border bg-card text-card-foreground shadow-sm my-4 mx-5 p-4' }, [
		H2({ cache: 'header', class: 'scroll-m-20 text-lg font-bold tracking-tight' }, 'Sign Out Test'),
		P({ class: 'text-muted-foreground my-2' }, 'This will test the app sign out.'),
		Button({ variant: 'outline', click: () => app.signOut() }, 'Sign Out')
	])
);

/**
 * HomePage
 *
 * This will create a home page.
 *
 * @returns {FullPage}
 */
export const HomePage = () => (
	new FullPage(
		FullProps(),
		[
			GreetingCard(),
			BindCard(),
			SignOutCard(),
			GridContainer()
		]
	)
);