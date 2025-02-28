import { H4, Li, Section, Ul } from "@base-framework/atoms";
import { DocPage } from "../../../../doc-page.js";

/**
 * DesignerSkillsPage
 *
 * This page provides an overview of key design concepts and skills
 * for our team members.
 *
 * @returns {DocPage}
 */
export const DesignerSkillsPage = () => (
	DocPage(
		{
			title: "Designer Skills",
			description: `This guide is designed to help designers within our team grow their skills and advance their careers.
We've compiled a list of key concepts and resources, grouped into categories, that designers can use to expand their knowledge and improve their abilities. By focusing on these areas, designers can enhance their creative proficiency and contribute more effectively to our projects and products.`
		},
		[
			// Core Design Concepts
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-2xl font-bold" }, "Core Design Concepts"),
				Ul({ class: "list-disc pl-6 space-y-1" }, [
					Li("Understanding layout, composition, color theory, typography, and design hierarchy"),
					Li("Familiarity with design thinking, user-centered design, and iterative processes"),
					Li("Principles of effective UI/UX design and information architecture")
				])
			]),

			// Design Fundamentals
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-xl font-bold" }, "Design Fundamentals"),
				Ul({ class: "list-disc pl-6 space-y-1" }, [
					Li("Color theory, typography, and visual hierarchy"),
					Li("Style guides, pattern libraries, and brand guidelines"),
					Li("Atomic Design principles: atoms, molecules, organisms, templates, and pages")
				])
			]),

			// User Research & UX
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-xl font-bold" }, "User Research & UX"),
				Ul({ class: "list-disc pl-6 space-y-1" }, [
					Li("Conducting user interviews, surveys, and usability tests"),
					Li("Creating user personas, user journeys, and empathy maps"),
					Li("Information architecture, site mapping, and user flows")
				])
			]),

			// Communication & Collaboration
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-xl font-bold" }, "Communication & Collaboration"),
				Ul({ class: "list-disc pl-6 space-y-1" }, [
					Li("Presenting design ideas effectively to stakeholders and teams"),
					Li("Collaborating with developers, product managers, and QA"),
					Li("Establishing feedback loops, running design critiques, and iterating quickly")
				])
			]),

			// Design Principles, Patterns & Paradigms
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-xl font-bold" }, "Design Principles, Patterns & Paradigms"),
				Ul({ class: "list-disc pl-6 space-y-1" }, [
					Li("Heuristics, Gestalt principles, and best practices for UI/UX"),
					Li("Material Design, Human Interface Guidelines, Fluent Design"),
					Li("Common design patterns (cards, modals, microinteractions, etc.)")
				])
			]),

			// Information Architecture & Content Strategy
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-xl font-bold" }, "Information Architecture & Content Strategy"),
				Ul({ class: "list-disc pl-6 space-y-1" }, [
					Li("Structuring sitemaps, content audits, and organizing data effectively"),
					Li("Designing clear navigation, labeling systems, and search functionality"),
					Li("Defining content hierarchy, user flows, and cross-linking strategies")
				])
			]),

			// Prototyping & Testing
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-xl font-bold" }, "Prototyping & Testing"),
				Ul({ class: "list-disc pl-6 space-y-1" }, [
					Li("Creating low-fidelity and high-fidelity wireframes and prototypes"),
					Li("Using tools like Figma, Sketch, Adobe XD, or InVision for interactive prototypes"),
					Li("Usability testing, A/B testing, and iterative design improvements")
				])
			]),

			// Design Tools & Workflow
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-xl font-bold" }, "Design Tools & Workflow"),
				Ul({ class: "list-disc pl-6 space-y-1" }, [
					Li("Figma, Sketch, Adobe Creative Cloud, and other prototyping/collaboration tools"),
					Li("Version control for design files (e.g., Abstract, Git-LFS)"),
					Li("Collaborative whiteboarding and brainstorming platforms like Miro")
				])
			]),

			// Accessibility & Inclusive Design
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-xl font-bold" }, "Accessibility & Inclusive Design"),
				Ul({ class: "list-disc pl-6 space-y-1" }, [
					Li("Following WCAG guidelines, ensuring contrast ratios and keyboard navigation"),
					Li("Designing for diverse audiences, including color-blindness and screen readers"),
					Li("Using semantic markup and ARIA roles in collaboration with developers")
				])
			]),

			// Responsive & Adaptive Design
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-xl font-bold" }, "Responsive & Adaptive Design"),
				Ul({ class: "list-disc pl-6 space-y-1" }, [
					Li("Designing mobile-first layouts, fluid grids, and flexible images"),
					Li("Planning breakpoints for various devices and screen sizes"),
					Li("Adapting content and interactions for different contexts and platforms")
				])
			]),

			// Branding & Visual Identity
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-xl font-bold" }, "Branding & Visual Identity"),
				Ul({ class: "list-disc pl-6 space-y-1" }, [
					Li("Defining and maintaining brand guidelines, logos, and color palettes"),
					Li("Establishing a consistent visual language across products and touchpoints"),
					Li("Incorporating tone of voice, imagery, and typography to reinforce brand identity")
				])
			]),

			// System Design & Handoff
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-xl font-bold" }, "System Design & Handoff"),
				Ul({ class: "list-disc pl-6 space-y-1" }, [
					Li("Creating shared libraries, design tokens, and reusable components"),
					Li("Providing detailed specs, redlines, and documentation for developers"),
					Li("Ensuring consistent experiences across platforms and products")
				])
			]),

			// Continuous Improvement & Professional Growth
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-xl font-bold" }, "Continuous Improvement & Professional Growth"),
				Ul({ class: "list-disc pl-6 space-y-1" }, [
					Li("Staying updated with design trends, emerging tools, and new technologies"),
					Li("Participating in workshops, conferences, and online design communities"),
					Li("Seeking mentorship, giving back, and fostering a culture of learning")
				])
			])
		]
	)
);

export default DesignerSkillsPage;
