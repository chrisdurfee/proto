// filepath: c:\xampp\htdocs\team\src\components\pages\home\components\pages\goals\goals-page.js
import { H4, Li, Ol, P, Section, Span } from "@base-framework/atoms";
import { DocPage } from "../../../../doc-page.js";

/**
 * GoalsPage
 *
 * This will create the Goals page.
 *
 * @returns {DocPage}
 */
export const GoalsPage = () => (
	DocPage(
		{
			title: 'Goals',
			description: `The department's goals are essential to aligning our efforts with the company's overall vision and ensuring that we continually improve our products, services, and team dynamics. This document outlines our department's key goals and provides a framework for measuring our progress and success.`
		},
		[
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-2xl font-bold' }, 'Department Goals'),
				Ol({ class: 'flex flex-col space-y-8 list-decimal pl-6' }, [
					// Deliver innovative products and services
					Li([
						Span({ class: 'text-lg font-bold' }, 'Deliver innovative products and services: '),
						P({ class: 'text-muted-foreground' }, "Align with the company's vision by developing innovative products and services that cater to our clients' needs and allow for scalability and pivotability. Continuously refine our offerings to stay ahead of industry trends and exceed client expectations.")
					]),
					// Cultivate a community of collaboration and learning
					Li([
						Span({ class: 'text-lg font-bold' }, 'Cultivate a community of collaboration and learning: '),
						P({ class: 'text-muted-foreground' }, 'Foster an environment that encourages collaboration, knowledge sharing, and continuous learning. Empower team members to grow their skills, share their expertise, and contribute to the success of our products, talents, and opportunities.')
					]),
					// Pursue progress through experimentation
					Li([
						Span({ class: 'text-lg font-bold' }, 'Pursue progress through experimentation: '),
						P({ class: 'text-muted-foreground' }, 'Embrace a culture of experimentation and risk-taking, allowing for the exploration of new ideas, tools, and processes. Continuously seek opportunities for improvement, both internally and externally, to ensure that our products and services remain at the forefront of the industry.')
					]),
					// Drive efficiency and effectiveness
					Li([
						Span({ class: 'text-lg font-bold' }, 'Drive efficiency and effectiveness: '),
						P({ class: 'text-muted-foreground' }, 'Streamline our processes, workflows, and communication channels to maximize productivity and ensure timely delivery of high-quality products and services. Regularly review and refine our methods to optimize performance and resource utilization.')
					]),
					// Maintain a strong focus on client satisfaction
					Li([
						Span({ class: 'text-lg font-bold' }, 'Maintain a strong focus on client satisfaction: '),
						P({ class: 'text-muted-foreground' }, "Develop a deep understanding of our clients' needs, preferences, and pain points, and use this insight to tailor our solutions accordingly. Ensure prompt and professional communication with clients, actively seeking their feedback and incorporating it into our development process.")
					])
				])
			])
		]
	)
);

export default GoalsPage;
