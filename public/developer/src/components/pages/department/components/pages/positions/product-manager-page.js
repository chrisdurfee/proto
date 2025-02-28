import { H4, Li, Section, Ul } from '@base-framework/atoms';
import { DocPage } from '../../../../doc-page.js';

/**
 * ProductManagerPage
 *
 * This page outlines the expectations, responsibilities and goals for the Product Manager position.
 *
 * @returns {DocPage}
 */
export const ProductManagerPage = () => (
	DocPage(
		{
			title: 'Product Manager',
			description: `The Product Manager is a key member of the Product Team, responsible for implementing practices and processes that enable the team to effectively execute project deliverables. This includes defining, communicating, and ensuring proper scope, deadlines, and resource allocation for all projects. The Product Manager also serves as the primary communicator of project progress to stakeholders. This document outlines the expectations, responsibilities, and goals associated with the Product Manager position.`
		},
		[
			// Responsibilities Section
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-2xl font-bold' }, 'Responsibilities'),
				Ul({ class: 'list-disc pl-6 space-y-1 text-muted-foreground' }, [
					Li("Define and communicate the product vision and strategy, aligning with the company's overall objectives."),
					Li("Collaborate with stakeholders to gather product requirements, identify user needs, and prioritize product features."),
					Li("Develop and maintain a well-organized product backlog, ensuring all items are clearly defined and prioritized."),
					Li("Collaborate with the development team to plan and execute product development sprints using Agile methodologies."),
					Li("Act as the primary liaison between the development team and stakeholders, communicating project progress and gathering feedback."),
					Li("Conduct market research, monitor competitor products, and analyze user feedback to inform product decisions and improvements."),
					Li("Ensure product quality and user satisfaction by collaborating with the Quality Assurance team and monitoring product performance."),
					Li("Participate in product releases, marketing efforts, and other organizational activities related to product management.")
				])
			]),
			// Goals Section
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-2xl font-bold' }, 'Goals'),
				Ul({ class: 'list-disc pl-6 space-y-1 text-muted-foreground' }, [
					Li("Develop and maintain a deep understanding of customer needs and market trends to inform product strategy and prioritization."),
					Li("Ensure all product goals and objectives are aligned with the company vision."),
					Li("Collaborate effectively with cross-functional teams to deliver high-quality products on time and within budget."),
					Li("Drive continuous improvement in the product development process, incorporating feedback and learnings from past projects."),
					Li("Foster a culture of innovation and creativity within the Product Team, encouraging the exploration of new ideas and approaches."),
					Li("Establish and maintain strong relationships with key stakeholders, both internally and externally, to ensure effective communication and collaboration.")
				])
			])
		]
	)
);

export default ProductManagerPage;
