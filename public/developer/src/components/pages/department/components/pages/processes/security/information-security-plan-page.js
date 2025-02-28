import { H4, Li, Section, Ul } from "@base-framework/atoms";
import { DocPage } from "../../../../../doc-page.js";

/**
 * InformationSecurityPlanPage
 *
 * This page outlines the Information Security Plan for our company.
 *
 * @returns {DocPage}
 */
export const InformationSecurityPlanPage = () => (
	DocPage(
		{
			title: "Information Security Plan",
			description:
				"This Information Security Plan outlines our approach to safeguarding our clients' data and ensuring the security and privacy of our systems and networks."
		},
		[
			// Purpose
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-2xl font-bold" }, "Purpose"),
				Ul({ class: "list-disc pl-6 space-y-1 text-muted-foreground" }, [
					Li("The purpose of this plan is to establish a comprehensive set of policies and procedures that protect the confidentiality, integrity, and availability of the information assets and systems at our company.")
				])
			]),

			// Scope
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-2xl font-bold" }, "Scope"),
				Ul({ class: "list-disc pl-6 space-y-1 text-muted-foreground" }, [
					Li("This plan covers all aspects of information security within our company, including but not limited to network security, data protection, access control, software development, employee training, and incident response.")
				])
			]),

			// Roles and Responsibilities
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-2xl font-bold" }, "Roles and Responsibilities"),
				Ul({ class: "list-disc pl-6 space-y-1 text-muted-foreground" }, [
					Li("The Information Security Officer (ISO) is responsible for overseeing the implementation and maintenance of this plan and coordinating security efforts across the organization."),
					Li("Department managers are responsible for ensuring their teams adhere to the security policies and procedures outlined in this plan."),
					Li("All employees are responsible for understanding and following the security policies and procedures applicable to their role.")
				])
			]),

			// Risk Assessment and Management
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-2xl font-bold" }, "Risk Assessment and Management"),
				Ul({ class: "list-disc pl-6 space-y-1 text-muted-foreground" }, [
					Li("We will perform an annual risk assessment to identify potential threats and vulnerabilities to our information assets and systems."),
					Li("Appropriate risk mitigation strategies will be implemented based on the identified risks, and these strategies will be periodically reviewed and updated as needed.")
				])
			]),

			// Network Security
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-2xl font-bold" }, "Network Security"),
				Ul({ class: "list-disc pl-6 space-y-1 text-muted-foreground" }, [
					Li("We will implement and maintain appropriate network security controls, such as firewalls, intrusion detection/prevention systems (IDS/IPS), and secure configurations for network devices."),
					Li("Regular network vulnerability scanning and penetration testing will be conducted to identify and remediate potential vulnerabilities.")
				])
			]),

			// Data Protection
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-2xl font-bold" }, "Data Protection"),
				Ul({ class: "list-disc pl-6 space-y-1 text-muted-foreground" }, [
					Li("We will employ encryption, access controls, and backup strategies to protect sensitive data in storage, transmission, and processing."),
					Li("Data retention and disposal policies will be implemented to ensure the secure storage and disposal of data when no longer needed.")
				])
			]),

			// Access Control
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-2xl font-bold" }, "Access Control"),
				Ul({ class: "list-disc pl-6 space-y-1 text-muted-foreground" }, [
					Li("We will enforce the principle of least privilege, granting employees the minimum level of access necessary to perform their job functions."),
					Li("User access to systems and data will be periodically reviewed, and access rights will be revoked or modified when employees change roles or leave the organization.")
				])
			]),

			// Software Development
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-2xl font-bold" }, "Software Development"),
				Ul({ class: "list-disc pl-6 space-y-1 text-muted-foreground" }, [
					Li("We will incorporate security best practices, such as secure coding techniques, code review, and vulnerability scanning into our software development process."),
					Li("All software updates and patches will be tested and deployed promptly to minimize potential security risks.")
				])
			]),

			// Employee Training and Awareness
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-2xl font-bold" }, "Employee Training and Awareness"),
				Ul({ class: "list-disc pl-6 space-y-1 text-muted-foreground" }, [
					Li("We will provide regular security awareness training to all employees to ensure they understand their role in protecting the organization's information assets and systems."),
					Li("Employees will also be informed of any changes or updates to security policies and procedures.")
				])
			]),

			// Incident Response
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-2xl font-bold" }, "Incident Response"),
				Ul({ class: "list-disc pl-6 space-y-1 text-muted-foreground" }, [
					Li("We will establish an incident response plan to guide the organization's response to security incidents."),
					Li("All employees are required to report suspected security incidents to the Information Security Officer (ISO) or their supervisor."),
					Li("Incidents will be investigated, and appropriate corrective actions will be taken to prevent a recurrence.")
				])
			]),

			// Compliance and Audit
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-2xl font-bold" }, "Compliance and Audit"),
				Ul({ class: "list-disc pl-6 space-y-1 text-muted-foreground" }, [
					Li("We will periodically review and update this Information Security Plan to ensure compliance with applicable laws, regulations, and industry standards."),
					Li("Internal and external audits will be conducted to verify the effectiveness of our information security controls and identify areas for improvement.")
				])
			]),

			// Commitment
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-2xl font-bold" }, "Commitment"),
				Ul({ class: "list-disc pl-6 space-y-1 text-muted-foreground" }, [
					Li("By implementing this Information Security Plan, we are committed to maintaining the highest levels of information security, ensuring the confidentiality, integrity, and availability of our clients' data and our systems and networks.")
				])
			])
		]
	)
);

export default InformationSecurityPlanPage;