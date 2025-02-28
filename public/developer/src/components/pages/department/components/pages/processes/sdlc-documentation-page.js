import { H4, Li, P, Section, Ul } from "@base-framework/atoms";
import { DocPage } from "../../../../doc-page.js";

/**
 * SDLCDocumentationPage
 *
 * This page provides comprehensive documentation for the End-to-End Software
 * Development Life Cycle (SDLC) process.
 *
 * @returns {DocPage}
 */
export const SDLCDocumentationPage = () => (
	DocPage(
		{
			title: "End-to-End SDLC Documentation",
			description: "This document outlines the end-to-end software development life cycle (SDLC) process, detailing the various stages from product vision and roadmap through deployment, engineering deliverables, reports, workflow, and core tools used."
		},
		[
			// Product Vision and Roadmap
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-2xl font-bold" }, "Product Vision and Roadmap"),
				P(
					"The product vision defines the overall purpose and goal of the software product, while the product roadmap outlines the strategic plan including features, functionality, and milestones. This stage involves:"
				),
				Ul({ class: "list-disc pl-6 space-y-1 text-muted-foreground" }, [
					Li("Defining the product vision"),
					Li("Identifying business needs and opportunities through market research, customer feedback, and trend analysis"),
					Li("Defining product goals with SMART (Specific, Measurable, Attainable, Relevant, Time-bound) criteria"),
					Li("Developing the product strategy, including target market, value proposition, and competitive advantage"),
					Li("Creating a product roadmap with key features, milestones, release timelines, and assessing staffing needs")
				])
			]),

			// Requirements
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-2xl font-bold" }, "Requirements"),
				P("This stage focuses on gathering and analyzing software requirements. It involves:"),
				Ul({ class: "list-disc pl-6 space-y-1 text-muted-foreground" }, [
					Li("Eliciting requirements by engaging stakeholders through interviews, workshops, and surveys"),
					Li("Analyzing requirements to review, categorize, and prioritize based on business value, feasibility, and risks"),
					Li("Defining functional and non-functional requirements that specify features and performance criteria"),
					Li("Documenting and validating requirements with a clear and complete requirements document"),
					Li("Researching the best libraries, frameworks, tools, and other essential resources for the project")
				])
			]),

			// Design
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-2xl font-bold" }, "Design"),
				P(
					"The design phase involves creating a detailed blueprint of the software architecture, user interface, and data models. This includes:"
				),
				Ul({ class: "list-disc pl-6 space-y-1 text-muted-foreground" }, [
					Li("Architectural design: Defining the high-level structure, components, and their interactions"),
					Li("User interface design: Crafting the visual layout, navigation, and interaction elements for an intuitive experience"),
					Li("Database design: Modeling the structure, relationships, and constraints of the data storage system"),
					Li("System integration design: Specifying interfaces and integration points with external systems"),
					Li("Reviewing security standards to ensure the design is secure across all environments")
				])
			]),

			// Implementation
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-2xl font-bold" }, "Implementation"),
				P("This phase is where the actual coding and development of the software take place. It includes:"),
				Ul({ class: "list-disc pl-6 space-y-1 text-muted-foreground" }, [
					Li("Setting up the development environment by configuring tools, libraries, and frameworks"),
					Li("Setting up servers and infrastructure for testing, staging, and production"),
					Li("Establishing DevOps pipelines to automate service and feature builds"),
					Li("Writing code according to design specifications and coding standards"),
					Li("Performing code reviews to ensure quality, readability, security, and maintainability"),
					Li("Integrating individual components to form the complete system")
				])
			]),

			// Testing
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-2xl font-bold" }, "Testing"),
				P(
					"Testing is critical to ensure that the software meets requirements and is free of defects. This stage includes:"
				),
				Ul({ class: "list-disc pl-6 space-y-1 text-muted-foreground" }, [
					Li("Developing a test plan that outlines the testing approach, objectives, resources, and schedule"),
					Li("Designing test cases with detailed scenarios, input data, expected results, and acceptance criteria"),
					Li("Executing tests including unit, integration, system, performance, and security tests"),
					Li("Reporting and fixing defects by documenting issues, prioritizing resolutions, and verifying fixes")
				])
			]),

			// Deployment
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-2xl font-bold" }, "Deployment"),
				P(
					"Deployment involves installing the software in the production environment and making it available to end users. It includes:"
				),
				Ul({ class: "list-disc pl-6 space-y-1 text-muted-foreground" }, [
					Li("Planning deployment with defined processes, prerequisites, rollback strategies, and timelines"),
					Li("Preparing the release by packaging the software along with configuration files, scripts, and documentation"),
					Li("Deploying to production by installing, configuring, and launching the software"),
					Li("Monitoring and validating the deployment to ensure proper functionality and gather performance metrics")
				])
			]),

			// Maintenance
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-2xl font-bold" }, "Maintenance"),
				P("The maintenance stage involves ongoing support, bug fixes, and enhancements. It includes:"),
				Ul({ class: "list-disc pl-6 space-y-1 text-muted-foreground" }, [
					Li("Troubleshooting and resolving issues by identifying root causes and implementing fixes"),
					Li("Monitoring performance to detect bottlenecks and capacity issues"),
					Li("Applying updates and patches to keep the software current with security fixes and feature enhancements"),
					Li("Retiring or replacing software that no longer meets business needs or has become obsolete")
				])
			]),

			// User Feedback
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-2xl font-bold" }, "User Feedback"),
				P("User feedback is essential for refining and enhancing the product. This stage involves:"),
				Ul({ class: "list-disc pl-6 space-y-1 text-muted-foreground" }, [
					Li("Allowing customers to report defects or request new features"),
					Li("Establishing regular sessions for product managers and designers to validate flows with users"),
					Li("Reporting findings to the product manager for evaluation and potential inclusion in the backlog")
				])
			]),

			// Engineering Deliverables
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-2xl font-bold" }, "Engineering Deliverables"),
				P(
					"Engineering deliverables are produced throughout the SDLC and include documentation, source code, test plans, and reports. Key deliverables include:"
				),
				Ul({ class: "list-disc pl-6 space-y-1 text-muted-foreground" }, [
					Li(
						"Requirement specifications: Functional and non-functional requirements, use case diagrams, and user stories"
					),
					Li("Design documents: Architectural, component, database, and UI designs"),
					Li(
						"Source code and unit tests: The codebase, accompanying documentation, and unit tests with code coverage metrics"
					),
					Li(
						"Test plans and test cases: Detailed plans, individual test cases, automated test scripts, and reports"
					),
					Li(
						"Deployment and maintenance guides: Documentation for deployment processes, configuration management, maintenance procedures, and troubleshooting"
					)
				])
			]),

			// Reports
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-2xl font-bold" }, "Reports"),
				P(
					"Reports are generated throughout the SDLC to track progress, performance, and accountability. Examples include:"
				),
				Ul({ class: "list-disc pl-6 space-y-1 text-muted-foreground" }, [
					Li(
						"Project status reports: Progress summaries, schedule updates, resource utilization, and risk/issue updates"
					),
					Li(
						"Test execution reports: Detailed execution of test cases, pass/fail summaries, and coverage analysis"
					),
					Li(
						"Defect tracking reports: Summaries of identified defects, trends, root cause analyses, and impact assessments"
					),
					Li(
						"Performance and security reports: Metrics, analysis, and testing results related to performance and security"
					)
				])
			]),

			// Workflow
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-2xl font-bold" }, "Workflow"),
				P("Our workflow follows an Agile-like system involving several key steps:"),
				Ul({ class: "list-disc pl-6 space-y-1 text-muted-foreground" }, [
					Li(
						"Planning and estimation: Defining project scope, gathering and analyzing requirements, and creating a work breakdown structure"
					),
					Li(
						"Task assignment and scheduling: Prioritizing tasks, allocating resources, scheduling tasks, and establishing milestones"
					),
					Li(
						"Progress tracking and reporting: Monitoring task completion, updating project status, and communicating progress"
					),
					Li(
						"Change management and version control: Implementing a change control process, managing change requests, and integrating version control"
					),
					Li(
						"Risk management and mitigation: Identifying, assessing, and mitigating risks throughout the project lifecycle"
					),
					Li(
						"Continuous improvement: Conducting retrospectives, gathering feedback, and implementing process enhancements"
					)
				])
			]),

			// Core Tools
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-2xl font-bold" }, "Core Tools"),
				P(
					"A variety of tools are utilized throughout the SDLC to streamline processes, facilitate collaboration, and boost productivity. Core tools include:"
				),
				Ul({ class: "list-disc pl-6 space-y-1 text-muted-foreground" }, [
					Li("Project management tools: Jira, Trello"),
					Li("Version control systems: Bitbucket"),
					Li("Integrated Development Environments (IDEs): Visual Studio Code, Eclipse, JetBrains variants"),
					Li("Build automation and CI tools: Bitbucket Pipelines"),
					Li("Continuous Delivery tools: Argo"),
					Li("Test management and automation tools: Selenium, Jest, Vitest, PHPUnit"),
					Li("Code review tools: GitHub, Bitbucket, Amazon CodeGuru"),
					Li("Documentation tools: Confluence"),
					Li("Package managers: npm, Composer"),
					Li("Development Containers: Docker"),
					Li("Design tools: Figma, Photoshop, AI image tools like Midjourney"),
					Li("Communication and collaboration tools: Microsoft Teams, Jira, Figma")
				])
			])
		]
	)
);

export default SDLCDocumentationPage;