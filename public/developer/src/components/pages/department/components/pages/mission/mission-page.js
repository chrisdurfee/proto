import { Div, H4, P, Section, Span } from "@base-framework/atoms";
import { DocPage } from "../../../../doc-page.js";

/**
 * MissionPage
 *
 * This will create the Mission page.
 *
 * @returns {DocPage}
 */
export const MissionPage = () => (
	DocPage(
		{
			title: 'Mission',
			description: `Our department's mission drives every decision, project, and interaction within our team. This document outlines our mission, its key components, and how we work towards achieving it.`
		},
		[
			// Mission Statement Section
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-2xl font-bold' }, 'Mission Statement'),
				P({ class: 'text-muted-foreground' }, 'To develop innovative, demand-driven, user-derived solutions for our clients by balancing form and function, allowing for large-scale adoption by abstracting technology from the solution.')
			]),

			// Key Concepts Section
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-2xl font-bold' }, 'Key Concepts'),
				Div({ class: 'flex flex-col space-y-8' }, [
					Div([
						Span({ class: 'text-lg' }, 'Innovative Solutions: '),
						P({ class: 'text-muted-foreground' }, 'We strive to create cutting-edge solutions that leverage the latest technologies and industry best practices. We consistently challenge ourselves to think outside the box and explore new approaches to deliver exceptional value to our clients.')
					]),
					Div([
						Span({ class: 'text-lg' }, 'Demand-driven and User-derived: '),
						P({ class: 'text-muted-foreground' }, 'Our solutions are designed with the end-users in mind, ensuring that we address their needs, preferences, and pain points. We engage with clients and users to gain insights and feedback, which we incorporate into our development process to create tailored solutions that truly make a difference.')
					]),
					Div([
						Span({ class: 'text-lg' }, 'Balancing Form and Function: '),
						P({ class: 'text-muted-foreground' }, 'We believe that aesthetics and usability go hand in hand. Our team focuses on striking the right balance between form and function, ensuring that our solutions not only look visually appealing but also provide a seamless and intuitive user experience.')
					]),
					Div([
						Span({ class: 'text-lg' }, 'Large-scale Adoption: '),
						P({ class: 'text-muted-foreground' }, 'Our goal is to develop solutions that are easily adoptable across a wide range of users and environments. We achieve this by emphasizing simplicity, scalability, and compatibility, making it easy for clients to integrate our solutions into their existing systems and workflows.')
					]),
					Div([
						Span({ class: 'text-lg' }, 'Abstracting Technology from the Solution: '),
						P({ class: 'text-muted-foreground' }, 'We recognize the importance of making technology accessible and easy to use for all users, regardless of their technical expertise. By abstracting technology from the solution, we ensure that our clients can focus on their core business objectives while benefiting from our innovative solutions without being overwhelmed by technical complexities.')
					])
				])
			])
		]
	)
);

export default MissionPage;
