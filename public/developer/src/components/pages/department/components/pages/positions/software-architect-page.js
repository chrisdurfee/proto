// filepath: c:\xampp\htdocs\team\src\components\pages\department\components\pages\positions\software-architect-page.js
import { H4, Li, Section, Ul } from '@base-framework/atoms';
import { DocPage } from '../../../../doc-page.js';

/**
 * SoftwareArchitectPage
 *
 * This page outlines the expectations, responsibilities and goals for the Software Architect position.
 *
 * @returns {DocPage}
 */
export const SoftwareArchitectPage = () => (
	DocPage(
		{
			title: 'Software Architect',
			description: `The Software Architect is a key member of the Technical Department, responsible for designing and implementing the underlying architecture of our software solutions. This role requires a deep understanding of software design principles, the ability to work effectively with cross-functional teams, and the vision to ensure that our software solutions remain scalable, maintainable, and secure. This document outlines the expectations, responsibilities, and goals associated with the Software Architect position.`
		},
		[
			// Responsibilities Section
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-2xl font-bold' }, 'Responsibilities'),
				Ul({ class: 'list-disc pl-6 space-y-1 text-muted-foreground' }, [
					Li('Design and develop the overall architecture of our software solutions, ensuring that they are scalable, maintainable, and secure.'),
					Li('Collaborate with cross-functional teams to gather requirements, define system specifications, and identify potential solutions.'),
					Li('Review and evaluate existing systems, identifying areas for improvement or optimization.'),
					Li('Stay up-to-date with industry trends and best practices, ensuring that our software solutions remain innovative and competitive.'),
					Li('Mentor and provide guidance to other team members on software design principles and best practices.'),
					Li('Participate in code reviews, ensuring that our software solutions adhere to established standards and guidelines.'),
					Li('Collaborate with the Product Team to ensure that our software solutions align with product goals and business objectives.')
				])
			]),
			// Goals Section
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-2xl font-bold' }, 'Goals'),
				Ul({ class: 'list-disc pl-6 space-y-1 text-muted-foreground' }, [
					Li('Continuously improve the quality, performance, and security of our software solutions.'),
					Li('Foster a culture of collaboration and knowledge sharing within the department.'),
					Li("Develop and maintain a deep understanding of our company's business objectives, ensuring that our software solutions support and advance these objectives."),
					Li('Stay up-to-date with industry trends and best practices, incorporating new ideas and approaches into your work.'),
					Li('Ensure that our software solutions are designed with scalability and maintainability in mind, allowing us to effectively respond to changing market conditions and user needs.'),
					Li('Build strong relationships with other team members and stakeholders, ensuring effective communication and collaboration.')
				])
			])
		]
	)
);

export default SoftwareArchitectPage;
