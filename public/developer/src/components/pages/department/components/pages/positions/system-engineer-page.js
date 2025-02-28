// filepath: c:\xampp\htdocs\team\src\components\pages\department\components\pages\positions\system-engineer-page.js
import { H4, Li, Section, Ul } from '@base-framework/atoms';
import { DocPage } from '../../../../doc-page.js';

/**
 * SystemEngineerPage
 *
 * This page outlines the expectations, responsibilities and goals for the System Engineer position.
 *
 * @returns {DocPage}
 */
export const SystemEngineerPage = () => (
	DocPage(
		{
			title: 'System Engineer',
			description: `The System Engineer is an essential role within the Technical Department, responsible for designing, implementing, and maintaining the infrastructure and systems that support our software solutions. This role requires strong technical skills, a solid understanding of various systems and networking concepts, and the ability to collaborate effectively within a dynamic, Agile environment. This document outlines the expectations, responsibilities, and goals associated with the System Engineer position.`
		},
		[
			// Responsibilities Section
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-2xl font-bold' }, 'Responsibilities'),
				Ul({ class: 'list-disc pl-6 space-y-1 text-muted-foreground' }, [
					Li('Research new software, tools, practices, and patterns to stay informed about industry trends and identify opportunities for improvement.'),
					Li('Create contingency plans for various situations, preparing game plans to address potential challenges and minimize downtime.'),
					Li('Implement disaster recovery plans for all systems, services, tools, and environments, ensuring business continuity and minimizing data loss.'),
					Li('Collaborate with platform providers to identify areas of improvement and opportunities to increase performance, security, redundancy, and scalability.'),
					Li('Provide feedback to the Project Manager about potential issues that need to be addressed and collaborate on priorities and tasks.'),
					Li('Research the issue or project, comparing competing products if applicable, and identifying the most suitable solution.'),
					Li('Research the issue or project. Compare competing products if one exists.'),
					Li('Deploy newly created or updated solutions, testing their implementation before rollout to ensure reliability and performance.'),
					Li('Secure systems, tools, services, and environments, implementing best practices and maintaining compliance with applicable laws and regulations.'),
					Li('Conduct regular audits of systems and procedures, reviewing any new implementations and identifying opportunities for improvement.')
				])
			]),
			// Goals Section
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-2xl font-bold' }, 'Goals'),
				Ul({ class: 'list-disc pl-6 space-y-1 text-muted-foreground' }, [
					Li('Ensure the availability, reliability, and performance of our infrastructure and systems, meeting or exceeding the needs of clients and internal teams.'),
					Li('Foster a culture of collaboration, knowledge sharing, and continuous improvement within the Technical Department.'),
					Li('Maintain a strong focus on security, ensuring that the infrastructure and systems you manage adhere to best practices and comply with applicable laws and regulations.'),
					Li('Contribute to the overall success of the Technical Department and the growth of our company by helping to ensure the efficient and secure operation of the company\'s infrastructure and systems.')
				])
			])
		]
	)
);

export default SystemEngineerPage;
