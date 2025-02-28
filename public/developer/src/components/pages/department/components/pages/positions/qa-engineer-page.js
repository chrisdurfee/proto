import { H4, Li, Section, Ul } from '@base-framework/atoms';
import { DocPage } from '../../../../doc-page.js';

/**
 * QAEngineerPage
 *
 * This page outlines the expectations, responsibilities and goals for the QA Engineer position.
 *
 * @returns {DocPage}
 */
export const QAEngineerPage = () => (
	DocPage(
		{
			title: 'QA Engineer',
			description: `The QA Engineer is an essential member of the department, responsible for ensuring the quality, reliability, and performance of our software solutions. This role requires a deep understanding of testing methodologies, attention to detail, and the ability to work effectively with cross-functional teams. This document outlines the expectations, responsibilities, and goals associated with the QA Engineer position.`
		},
		[
			// Responsibilities Section
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-2xl font-bold' }, 'Responsibilities'),
				Ul({ class: 'list-disc pl-6 space-y-1 text-muted-foreground' }, [
					Li("Develop and execute test plans, test cases, and test scripts for our software solutions, ensuring comprehensive coverage of all functional and non-functional requirements."),
					Li("Collaborate with development, product, and operations teams to identify, report, and track defects, working together to determine their root causes and implement corrective actions."),
					Li("Contribute to the continuous improvement of our testing processes, methodologies, and tools, ensuring that they remain effective and up-to-date."),
					Li("Participate in code reviews, ensuring that our software solutions adhere to established standards and guidelines."),
					Li("Stay up-to-date with industry trends and best practices, incorporating new ideas and approaches into your work."),
					Li("Assist in troubleshooting and resolving production issues, working closely with other team members to identify root causes and implement corrective actions."),
					Li("Provide input on product requirements and specifications, helping to ensure that they are clear, concise, and testable.")
				])
			]),
			// Goals Section
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-2xl font-bold' }, 'Goals'),
				Ul({ class: 'list-disc pl-6 space-y-1 text-muted-foreground' }, [
					Li("Continuously improve the quality, reliability, and performance of our software solutions, ensuring that they meet or exceed customer expectations."),
					Li("Foster a culture of collaboration, knowledge sharing, and continuous improvement within the department."),
					Li("Ensure that our testing processes and methodologies remain effective and efficient, allowing us to identify and address issues quickly and proactively."),
					Li("Maintain strong relationships with other team members and stakeholders, ensuring effective communication and collaboration."),
					Li("Contribute to the overall success of the department and the growth of our company by helping to ensure the delivery of high-quality software solutions.")
				])
			])
		]
	)
);

export default QAEngineerPage;
