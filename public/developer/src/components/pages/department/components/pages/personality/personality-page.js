import { Div, H4, P, Section, Span } from "@base-framework/atoms";
import { DocPage } from "../../../../doc-page.js";

/**
 * PersonalityPage
 *
 * This will create the Department Personality page.
 *
 * @returns {DocPage}
 */
export const PersonalityPage = () => (
	DocPage(
		{
			title: 'Personality',
			description: `The department's personality defines the way we interact with our colleagues, clients, and partners. This document outlines the core traits and values that make up our department's personality, guiding our actions, language, and behavior.`
		},
		[
			// Department Personality Traits Section
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-2xl font-bold' }, 'Department Personality Traits'),
				Div({ class: 'flex flex-col space-y-8' }, [
					Div([
						Span({ class: 'text-lg' }, 'Courteous and Respectful: '),
						P({ class: 'text-muted-foreground' }, 'We treat everyone with courtesy and respect, fostering a positive and welcoming work environment. We value open-mindedness and demonstrate empathy in our interactions with others, ensuring that everyone\'s opinions and ideas are valued and respected.')
					]),
					Div([
						Span({ class: 'text-lg' }, 'Knowledgeable and Understanding: '),
						P({ class: 'text-muted-foreground' }, 'We are committed to staying informed about industry trends, best practices, and emerging technologies. Our team members are encouraged to share their knowledge and expertise with others, promoting a culture of continuous learning and growth. We also strive to understand the needs and challenges of our clients, partners, and colleagues, allowing us to develop meaningful and effective solutions.')
					]),
					Div([
						Span({ class: 'text-lg' }, 'Collaborative and Elevating: '),
						P({ class: 'text-muted-foreground' }, 'We believe that collaboration is the key to success. Our team works together to achieve common goals, share ideas, and provide support to one another. We actively seek to elevate those around us by sharing our knowledge, providing constructive feedback, and offering guidance and mentorship.')
					])
				])
			]),
			// Department Commitment to Diversity and Inclusion Section
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-2xl font-bold' }, 'Department Commitment to Diversity and Inclusion'),
				Div({ class: 'flex flex-col space-y-8' }, [
					Div([
						Span({ class: 'text-lg' }, 'Inclusive and Supportive: '),
						P({ class: 'text-muted-foreground' }, 'Our department is dedicated to creating an inclusive and supportive environment for all team members, regardless of their race, religion, age, gender, background, culture, or perspective. We value diversity and believe that it enriches our team\'s creativity, innovation, and problem-solving abilities. We strive to create a safe and welcoming space where everyone can share their ideas, experiences, and opinions.')
					]),
					Div([
						Span({ class: 'text-lg' }, 'Team-oriented: '),
						P({ class: 'text-muted-foreground' }, 'We are a team-oriented department that values collaboration, shared goals, and collective success. By fostering a culture of teamwork, we ensure that every team member feels valued, included, and supported in their professional journey.')
					]),
					Div([
						Span({ class: 'text-lg' }, 'Embracing Diversity and Inclusion in Actions and Language: '),
						P({ class: 'text-muted-foreground' }, 'Our commitment to diversity and inclusion is not limited to our intentions; it is reflected in our actions and language. We strive to use inclusive language, avoid making assumptions, and remain open to learning about different perspectives and experiences. We also actively participate in company-wide diversity and inclusion initiatives and encourage team members to engage in diversity-related training and events.')
					])
				])
			])
		]
	)
);

export default PersonalityPage;
