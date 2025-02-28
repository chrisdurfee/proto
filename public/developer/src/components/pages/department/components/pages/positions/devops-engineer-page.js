// filepath: c:\xampp\htdocs\team\src\components\pages\department\components\pages\positions\devops-engineer-page.js
import { H4, Li, Section, Ul } from '@base-framework/atoms';
import { DocPage } from '../../../../doc-page.js';

/**
 * DevOpsEngineerPage
 *
 * This page outlines the expectations, responsibilities and goals for the DevOps Engineer position.
 *
 * @returns {DocPage}
 */
export const DevOpsEngineerPage = () => (
	DocPage(
		{
			title: 'DevOps Engineer',
			description: `The DevOps Engineer plays a crucial role in the department, ensuring the smooth operation and continuous improvement of our software development and deployment processes. This role requires a deep understanding of development, testing, and deployment methodologies, as well as the ability to collaborate effectively with cross-functional teams. This document outlines the expectations, responsibilities, and goals associated with the DevOps Engineer position.`
		},
		[
			// Responsibilities Section
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-2xl font-bold' }, 'Responsibilities'),
				Ul({ class: 'list-disc pl-6 space-y-1 text-muted-foreground' }, [
					Li("Design, implement, and maintain CI/CD pipelines, ensuring that our software solutions are built, tested, and deployed efficiently and reliably."),
					Li("Collaborate with development, testing, and operations teams to identify and address bottlenecks, inefficiencies, and other areas for improvement in the software development lifecycle."),
					Li("Monitor and manage our infrastructure, ensuring its availability, performance, and security."),
					Li("Develop and maintain automation tools and scripts to streamline deployment, testing, and other operational processes."),
					Li("Stay up-to-date with industry trends and best practices, incorporating new ideas and approaches into your work."),
					Li("Assist in troubleshooting and resolving production issues, working closely with other team members to identify root causes and implement corrective actions."),
					Li("Participate in code reviews, ensuring that our software solutions adhere to established standards and guidelines.")
				])
			]),
			// Goals Section
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-2xl font-bold' }, 'Goals'),
				Ul({ class: 'list-disc pl-6 space-y-1 text-muted-foreground' }, [
					Li("Continuously improve the efficiency, reliability, and performance of our CI/CD pipelines and infrastructure."),
					Li("Foster a culture of collaboration, knowledge sharing, and continuous improvement within the Technical Department."),
					Li("Ensure that our software solutions are developed, tested, and deployed in a manner that supports our business objectives and product goals."),
					Li("Maintain a high level of availability, performance, and security for our infrastructure."),
					Li("Build strong relationships with other team members and stakeholders, ensuring effective communication and collaboration.")
				])
			])
		]
	)
);

export default DevOpsEngineerPage;
