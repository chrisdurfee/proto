// filepath: c:\xampp\htdocs\team\src\components\pages\department\components\pages\positions\secops-engineer-page.js
import { H4, Li, Section, Ul } from '@base-framework/atoms';
import { DocPage } from '../../../../doc-page.js';

/**
 * SecOpsEngineerPage
 *
 * This page outlines the expectations, responsibilities and goals for the SecOps Engineer position.
 *
 * @returns {DocPage}
 */
export const SecOpsEngineerPage = () => (
	DocPage(
		{
			title: 'SecOps Engineer',
			description: `The SecOps Engineer is a crucial role within the Technical Department, responsible for ensuring the security, compliance, and integrity of our systems and applications. This document outlines the expectations, responsibilities, and goals associated with the SecOps Engineer position.`
		},
		[
			// Responsibilities Section
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-2xl font-bold' }, 'Responsibilities'),
				Ul({ class: 'list-disc pl-6 space-y-1 text-muted-foreground' }, [
					Li("Develop and implement security policies, procedures, and best practices to protect our systems and applications from security threats."),
					Li("Monitor and analyze security events and incidents, responding to and resolving any security breaches or vulnerabilities."),
					Li("Conduct regular security assessments and audits, ensuring that our systems and applications comply with industry standards and applicable laws and regulations."),
					Li("Collaborate with the development, operations, and other technical teams to ensure security is integrated into all aspects of the software development life cycle."),
					Li("Research and stay up-to-date on the latest security threats, trends, and technologies, identifying opportunities for improvement and mitigating potential risks."),
					Li("Develop and deliver security training and awareness programs for staff to ensure a strong security culture within the organization."),
					Li("Provide guidance and support to other team members on security-related issues, fostering a collaborative and knowledge-sharing environment.")
				])
			]),
			// Goals Section
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-2xl font-bold' }, 'Goals'),
				Ul({ class: 'list-disc pl-6 space-y-1 text-muted-foreground' }, [
					Li("Ensure the security, compliance, and integrity of our systems and applications, protecting sensitive data and maintaining client trust."),
					Li("Continuously improve the organization's security posture by identifying and mitigating potential risks and vulnerabilities."),
					Li("Foster a culture of collaboration, knowledge sharing, and continuous learning within the Technical Department, with a focus on security best practices."),
					Li("Contribute to the overall success of the Technical Department and the growth of our company by helping to ensure the secure and compliant operation of the company's systems and applications.")
				])
			])
		]
	)
);

export default SecOpsEngineerPage;
