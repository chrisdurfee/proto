// filepath: c:\xampp\htdocs\team\src\components\pages\department\components\pages\positions\software-engineer-page.js
import { H4, Li, Section, Ul } from '@base-framework/atoms';
import { DocPage } from '../../../../doc-page.js';

/**
 * SoftwareEngineerPage
 *
 * This page outlines the expectations, responsibilities, and goals for the Software Engineer position.
 *
 * @returns {DocPage}
 */
export const SoftwareEngineerPage = () => (
	DocPage(
		{
			title: 'Software Engineer',
			description: `The Software Engineer is a vital role within the department, responsible for designing, implementing, testing, and maintaining high-quality software solutions that meet the needs of our clients and internal teams. This role requires strong technical skills, problem-solving abilities, and the ability to work effectively within a collaborative, Agile development environment. This document outlines the expectations, responsibilities, and goals associated with the Software Engineer position.`
		},
		[
			// Responsibilities Section
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-2xl font-bold' }, 'Responsibilities'),
				Ul({ class: 'list-disc pl-6 space-y-1 text-muted-foreground' }, [
					Li("Collaborate with cross-functional teams, including product managers, designers, and other engineers, to design, develop, and deliver high-quality software solutions."),
					Li("Write clean, maintainable, and efficient code, adhering to established coding standards and best practices."),
					Li("Participate in code reviews, providing constructive feedback to your peers and incorporating feedback received from others to improve your own work."),
					Li("Test and debug software solutions, ensuring their stability, security, and performance."),
					Li("Continuously improve your technical skills and knowledge, staying up-to-date with industry trends and emerging technologies."),
					Li("Participate in Agile development processes, such as Scrum, ensuring effective collaboration and communication within the team."),
					Li("Document your work, including design decisions, implementation details, and any known issues or limitations, to facilitate future development efforts and knowledge sharing.")
				])
			]),
			// Goals Section
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-2xl font-bold' }, 'Goals'),
				Ul({ class: 'list-disc pl-6 space-y-1 text-muted-foreground' }, [
					Li("Consistently deliver high-quality software solutions that meet or exceed the needs of our clients and internal teams."),
					Li("Foster a culture of collaboration, knowledge sharing, and continuous improvement within the Technical Department."),
					Li("Maintain a strong focus on security, ensuring that the software solutions you develop adhere to best practices and comply with applicable laws and regulations."),
					Li("Stay up-to-date with industry trends and best practices, incorporating new ideas and approaches into your work."),
					Li("Contribute to the overall success of the Technical Department and the growth of our company by helping to ensure the timely delivery of innovative, demand-driven software solutions.")
				])
			])
		]
	)
);

export default SoftwareEngineerPage;
