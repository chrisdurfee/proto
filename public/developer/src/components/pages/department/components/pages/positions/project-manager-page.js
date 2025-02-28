import { H4, Li, Section, Ul } from '@base-framework/atoms';
import { DocPage } from '../../../../doc-page.js';

/**
 * ProjectManagerPage
 *
 * This page outlines the expectations, responsibilities and goals for the Project Manager position.
 *
 * @returns {DocPage}
 */
export const ProjectManagerPage = () => (
	DocPage(
		{
			title: 'Project Manager',
			description: `The Project Manager plays a crucial role in the Product Team, ensuring the effective execution of project deliverables, maintaining Agile Project Management practices, and serving as the primary communicator of project progress to stakeholders. This document outlines the expectations, responsibilities, and goals associated with the Project Manager position.`
		},
		[
			// Responsibilities Section
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-2xl font-bold' }, 'Responsibilities'),
				Ul({ class: 'list-disc pl-6 space-y-1 text-muted-foreground' }, [
					Li("Establish and maintain Agile Project Management practices for the Product Team to optimize efficiency and maintain focus on projects."),
					Li("Run meetings for the Product team to coordinate on project items and clear roadblocks to ensure progress is made."),
					Li("Define project scope, goals, and deliverables."),
					Li("Establish project roadmaps for our products."),
					Li("Create and continuously groom the product backlog and ensure that work items (stories and tasks) are clearly written and defined."),
					Li("Act as the team's main communicator: field and help vet all new product ideas, requests for product changes, updates, bugs, etc."),
					Li("Routinely communicate project progress to the Executive Team and other stakeholders."),
					Li("Mentor team members on Agile methodologies and foundational values and principles."),
					Li("Document Project Objectives, Key Outcomes, Details, Status, Scope Changes, and Final Deliverables."),
					Li("Participate in the organizational processes around product releases, strategies, standards, and processes."),
					Li("Play a role in product vision: provide insight into how we can build the best products for our clients."),
					Li("Build strong relationships on the team and throughout the organization to help propel our vision.")
				])
			]),
			// Goals Section
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-2xl font-bold' }, 'Goals'),
				Ul({ class: 'list-disc pl-6 space-y-1 text-muted-foreground' }, [
					Li("Ensure all project goals and objectives are aligned with the company vision."),
					Li("Ensure all projects are aligned with the product goal and vision."),
					Li("As an acting Scrum Master, coach, encourage, and enforce adoption of Scrum Theory, Pillars, Values, Artifacts, etc."),
					Li("Create an environment of collaboration and excellent communication."),
					Li("As much as is realistically possible, accurately determine Sprint Scope based on Sprint Artifacts."),
					Li("Determine Roadmap Timelines with accuracy."),
					Li("Identify inefficiencies and ways to improve as a team(s) and implement needed changes.")
				])
			])
		]
	)
);

export default ProjectManagerPage;
