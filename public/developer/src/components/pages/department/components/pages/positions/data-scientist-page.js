import { H4, Li, Section, Ul } from '@base-framework/atoms';
import { DocPage } from '../../../../doc-page.js';

/**
 * DataScientistPage
 *
 * This page outlines the expectations, responsibilities and goals for the Data Scientist position.
 *
 * @returns {DocPage}
 */
export const DataScientistPage = () => (
	DocPage(
		{
			title: 'Data Scientist',
			description: `The Data Scientist plays a key role in the department, leveraging data-driven insights to inform decision-making and drive innovation. This role requires a strong foundation in statistics, data analysis, machine learning, and the ability to communicate complex ideas to both technical and non-technical stakeholders. This document outlines the expectations, responsibilities, and goals associated with the Data Scientist position.`
		},
		[
			// Responsibilities Section
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-2xl font-bold' }, 'Responsibilities'),
				Ul({ class: 'list-disc pl-6 space-y-1 text-muted-foreground' }, [
					Li("Collect, clean, and preprocess data from various sources, ensuring its accuracy, consistency, and relevance."),
					Li("Conduct exploratory data analysis to identify trends, patterns, and anomalies that can inform decision-making and product development."),
					Li("Develop and implement machine learning models and algorithms to solve complex problems and generate actionable insights."),
					Li("Collaborate with cross-functional teams, including product, development, and operations, to translate data-driven insights into actionable recommendations and strategies."),
					Li("Present findings and recommendations to stakeholders in a clear, concise, and persuasive manner, using data visualization tools and techniques to enhance understanding."),
					Li("Continuously improve your skills and knowledge in data science, staying up-to-date with industry trends and best practices."),
					Li("Participate in code reviews, ensuring that our data-driven solutions adhere to established standards and guidelines.")
				])
			]),
			// Goals Section
			Section({ class: 'space-y-4 mt-12' }, [
				H4({ class: 'text-2xl font-bold' }, 'Goals'),
				Ul({ class: 'list-disc pl-6 space-y-1 text-muted-foreground' }, [
					Li("Continuously generate data-driven insights that inform decision-making and drive innovation within the Technical Department and our company as a whole."),
					Li("Foster a culture of collaboration, knowledge sharing, and continuous improvement within the Technical Department."),
					Li("Ensure the accuracy, consistency, and relevance of the data used in your analyses and models, minimizing the risk of incorrect or misleading conclusions."),
					Li("Develop and maintain strong relationships with other team members and stakeholders, ensuring effective communication and collaboration."),
					Li("Contribute to the overall success of the Technical Department and the growth of our company by helping to ensure the delivery of innovative, data-driven solutions.")
				])
			])
		]
	)
);

export default DataScientistPage;
