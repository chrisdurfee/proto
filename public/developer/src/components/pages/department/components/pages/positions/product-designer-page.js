// filepath: c:\xampp\htdocs\team\src\components\pages\department\components\pages\positions\product-designer-page.js
import { H4, Li, Section, Ul } from '@base-framework/atoms';
import { DocPage } from '../../../../doc-page.js';

/**
 * ProductDesignerPage
 *
 * This page outlines the expectations, responsibilities and goals for the Product Designer position.
 *
 * @returns {DocPage}
 */
export const ProductDesignerPage = () => (
	DocPage(
		{
			title: 'Product Designer',
			description: `The Product Designer plays a vital role in shaping the products and services used by our team members and clients. This role encompasses a broad range of responsibilities, from planning and research to design, development, testing, and maintenance. This document outlines the expectations, responsibilities, and goals associated with the Product Designer position.`
		},
		[
			// Responsibilities Section
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-2xl font-bold' }, 'Responsibilities'),
				Ul({ class: 'list-disc pl-6 space-y-1 text-muted-foreground' }, [
					Li("Collaborate with the Product Manager, Development Team, and other stakeholders to understand project requirements and user needs."),
					Li("Conduct user research, analyze user behavior, and create user personas to inform design decisions."),
					Li("Create wireframes, mockups, and prototypes to communicate and iterate on design ideas."),
					Li("Develop high-fidelity visual designs and user interfaces that adhere to our design guidelines and industry best practices."),
					Li("Conduct usability testing and gather user feedback to refine and improve the product design."),
					Li("Collaborate with developers to ensure the final product is aligned with the intended design and provides an excellent user experience."),
					Li("Stay up-to-date with the latest design trends, technologies, and tools, continuously improving your skills and contributing to the team's knowledge.")
				])
			]),
			// Goals Section
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-2xl font-bold' }, 'Goals'),
				Ul({ class: 'list-disc pl-6 space-y-1 text-muted-foreground' }, [
					Li("Deliver user-centered designs that address the needs and pain points of our clients and users."),
					Li("Consistently improve the usability, accessibility, and overall user experience of our products and services."),
					Li("Collaborate effectively with cross-functional teams, ensuring that designs are aligned with product goals and business objectives."),
					Li("Stay up-to-date with industry trends and best practices, incorporating new ideas and approaches into your work."),
					Li("Develop and maintain a deep understanding of our brand, ensuring that all designs are consistent with our visual identity and messaging."),
					Li("Continuously seek feedback from users, product managers, and stakeholders, using this information to inform your design decisions and improve your skills.")
				])
			])
		]
	)
);

export default ProductDesignerPage;
