import { Div, H4, P, Section, Span } from "@base-framework/atoms";
import { DocPage } from "../../../../doc-page.js";

/**
 * IntroPage
 *
 * This will create the Department Overview page.
 *
 * @returns {DocPage}
 */
export const IntroPage = () => (
	DocPage(
		{
            title: 'Introduction',
            description: `Welcome to the Product Department! We are excited to have you join our team. This document aims to provide you with an overview of our department's expectations, culture, goals, mission, and processes. It will serve as a guide to help you understand your role and responsibilities as a member of the team.

We strive to ship fast, iterate often, and solve actual problems to create innovative solutions.`,
        },
		[
			// Department Expectations Section
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-2xl font-bold' }, 'Department Expectations'),
				Div({ class: 'flex flex-col space-y-8' }, [
					Div([
						Span({ class: 'text-lg' }, 'Maintain professionalism: '),
						P({ class: 'text-muted-foreground' }, 'Uphold the company\'s values and adhere to established policies and procedures. Act with integrity, respect, and professionalism in all interactions with colleagues, clients, and stakeholders.')
					]),
					Div([
						Span({ class: 'text-lg' }, 'Collaborate effectively: '),
						P({ class: 'text-muted-foreground' }, 'Work together with your colleagues to achieve common goals, share ideas, and maintain open lines of communication. Embrace our teamwork philosophy by raising others and understanding that we can only succeed if we are all successful. We build great products by building great people.')
					]),
					Div([
						Span({ class: 'text-lg' }, 'Fear of failure: '),
						P({ class: 'text-muted-foreground' }, 'Cultivate an environment where the fear of failure does not exist. Be open to trying new things, taking calculated risks, and learning from the outcomes. Encourage experimentation and foster a culture of continuous improvement. This concept is what makes our team successful.')
					]),
					Div([
						Span({ class: 'text-lg' }, 'Take ownership: '),
						P({ class: 'text-muted-foreground' }, 'Take ownership of your projects. Add details of quality, polish, and care even if nobody asked for them. If you feel that something is missing or should be corrected to make the project better, add it in or propose the idea to get it added to the project backlog.')
					]),
					Div([
						Span({ class: 'text-lg' }, 'Continuously improve: '),
						P({ class: 'text-muted-foreground' }, 'Pursue learning opportunities and stay updated with the latest industry trends and technologies. Embrace our philosophy of shipping fast, iterating often, and solving actual problems to create innovative solutions. Learn from failures and use them as opportunities to grow and refine our processes.')
					])
				])
			]),

			// Reusable Assets and Quality Section
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-2xl font-bold' }, 'Reusable Assets and Deliver Quality'),
				Div({ class: 'flex flex-col space-y-8' }, [
					Div([
						Span({ class: 'text-lg' }, 'Reusable Assets: '),
						P({ class: 'text-muted-foreground' }, 'Abstract your project into base types and create reusable assets that can be used multiple times. Good developers write code, great developers write libraries.')
					]),
					Div([
						Span({ class: 'text-lg' }, 'Deliver quality: '),
						P({ class: 'text-muted-foreground' }, 'Strive to produce high-quality work and maintain the standards set by the department. Ensure that every aspect of the product, from design to implementation, meets our quality benchmarks and exceeds client expectations. We have four pillars to our code quality: resiliency, scalability, maintainability, and security.')
					])
				])
			]),

			// Department Processes Section
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-2xl font-bold' }, 'Department Processes'),
				Div({ class: 'flex flex-col space-y-8' }, [
					Div([
						Span({ class: 'text-lg' }, 'Security First: '),
						P({ class: 'text-muted-foreground' }, 'We build our projects locked down, then make them work only opening as little as necessary to do the function or task.')
					]),
					Div([
						Span({ class: 'text-lg' }, 'Project management: '),
						P({ class: 'text-muted-foreground' }, 'We utilize Agile methodologies, such as Scrum, to manage our projects and ensure timely delivery. We use Jira to track our projects and tasks.')
					]),
					Div([
						Span({ class: 'text-lg' }, 'Product Environments: '),
						P({ class: 'text-muted-foreground' }, 'All projects are developed to run in a browser environment. The product should be responsive in design and functionality, allowing usage on any modern device. We build each product as a Progressive Web Application (PWA) using modern JavaScript to render our applications on the client-side.')
					]),
					Div([
						Span({ class: 'text-lg' }, 'Atomic Development: '),
						P({ class: 'text-muted-foreground' }, 'Scalability and speed are a focus for our department. Atomic design and development allow for faster ideation, prototyping, and development because each element is created, shared, and reused. Each atom is created as an abstract type in the smallest form. Atoms can be added to other atoms creating molecules. Molecules and atoms can be combined to create organisms. Large layouts can be scaffolded using templates. Templates are abstract layouts that allow for consistency and reusability. Templates allow atoms, molecules, and organisms to be nested. Pages are generic types that use templates to speed up development and keep consistency throughout the project. Each new page extends from a base page type. The extended page reduces the code needed to write the new service because most functionality is inherited.')
					]),
					Div([
						Span({ class: 'text-lg' }, 'Test-Driven Development: '),
						P({ class: 'text-muted-foreground' }, 'We build our projects using test-driven development, allowing our pipelines to use automated testing in our CI/CD pipelines. We use PHPUnit for backend testing and Vitest for frontend testing.')
					]),
					Div([
						Span({ class: 'text-lg' }, 'Documentation: '),
						P({ class: 'text-muted-foreground' }, 'Comprehensive documentation is maintained to facilitate collaboration and ease future development efforts. We use JSDoc to document our JavaScript and PHPDoc to document our PHP. We also use Confluence to document our projects and processes.')
					]),
					Div([
						Span({ class: 'text-lg' }, 'Code & design reviews: '),
						P({ class: 'text-muted-foreground' }, 'All code and designs are subject to peer review to maintain quality standards and promote knowledge sharing.')
					]),
					Div([
						Span({ class: 'text-lg' }, 'Git-flow: '),
						P({ class: 'text-muted-foreground' }, 'We use the branching model Git-flow with all our project repositories. Commit often. Start every day by pulling and end by committing.')
					])
				])
			])
		]
	)
);

export default IntroPage;