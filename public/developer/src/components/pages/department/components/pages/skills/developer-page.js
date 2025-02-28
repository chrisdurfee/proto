import { H4, Li, Section, Ul } from "@base-framework/atoms";
import { DocPage } from "../../../../doc-page.js";

/**
 * DeveloperSkillsPage
 *
 * This page provides an overview of key development concepts and skills
 * for our team members.
 *
 * @returns {DocPage}
 */
export const DeveloperSkillsPage = () => (
	DocPage(
		{
			title: "Developer Skills",
			description: `This guide is designed to help developers within our team grow their skills and advance their careers.
We've compiled a list of key concepts and resources, grouped into categories, that developers can use to expand their knowledge and improve their abilities. By focusing on these areas, developers can enhance their technical proficiency and contribute more effectively to our projects and products.`
		},
		[
			// Core Development Concepts
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-2xl font-bold" }, "Core Development Concepts"),
			]),

			// Coding
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-xl font-bold" }, "Coding"),
				Ul({ class: "list-disc pl-6 space-y-1" }, [
					Li("Syntax, Operators, Logic, Flow Control, Variables, Classes, Properties, Methods, Static Methods, Abstract Classes and Methods, Interfaces, Final Classes, and Final Methods"),
					Li("HTML5, CSS3, JavaScript 6+"),
					Li("Component-Based Client-Side Rendering, Atomic design"),
					Li("Progressive Web Applications, TWA's, Web Push, Installing, Manifests, Service Workers"),
					Li("Object-Oriented Programming, Inheritance, Polymorphism, Encapsulation, Abstraction, Namespaces, Modules, Packages, and Libraries")
				])
			]),

			// Programming Languages
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-xl font-bold" }, "Programming Languages"),
				Ul({ class: "list-disc pl-6 space-y-1" }, [
					Li("PHP"),
					Li("JavaScript, TypeScript, ES6+"),
					Li("Rust"),
					Li("Golang"),
					Li("Python"),
					Li("C#"),
					Li("C++, Carbon, etc."),
					Li("Java, Kotlin, etc."),
					Li("Swift (iOS, macOS, watchOS, tvOS)"),
					Li("Dart (Flutter)"),
					Li("Elixir")
				])
			]),

			// Communication & Problem Solving
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-xl font-bold" }, "Communication & Problem Solving"),
				Ul({ class: "list-disc pl-6 space-y-1" }, [
					Li("How to Comment, Doc Block, Annotations, Documenting Code, Readable Code, Who are we writing code for?"),
					Li("Learning how to break problems down to engineer solutions, How to model data")
				])
			]),

			// Programming Principles, Patterns & Paradigms
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-xl font-bold" }, "Programming Principles, Patterns & Paradigms"),
				Ul({ class: "list-disc pl-6 space-y-1" }, [
					Li("S.O.L.I.D, KISS, DRY, Composition over Inheritance, YAGNI, Clean Code"),
					Li("Pub-Sub, Factory, Facade, Decorator, etc."),
					Li("OOP, Functional, Procedural, etc.")
				])
			]),

			// APIs & Databases
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-xl font-bold" }, "APIs & Databases"),
				Ul({ class: "list-disc pl-6 space-y-1" }, [
					Li("API: Rest, GraphQL, RPC, gRPC"),
					Li("Databases: SQL, No-SQL, Graph Databases, Vector Databases, Sharding, partitioning, profiling, indices, foreign keys, 5 keys to Database Normalization, Cardinality")
				])
			]),

			// Testing & Localization
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-xl font-bold" }, "Testing & Localization"),
				Ul({ class: "list-disc pl-6 space-y-1" }, [
					Li("Test-Driven Development, Dependency injection, Unit, Integration, End-to-End, Selenium, Jest, Vitest, PHPUnit"),
					Li("Localization, UTC Dates")
				])
			]),

			// Development Utils
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-xl font-bold" }, "Development Utils"),
				Ul({ class: "list-disc pl-6 space-y-1" }, [
					Li("Containers: Docker"),
					Li("Package managers: npm, composer"),
					Li("Code Bundlers: Vite, Web Pack"),
					Li("Git, Git-flow, trunk, pull requests, Monorepos, etc."),
					Li("CI/CD (Continuous Integration and Continuous Delivery/Deployment)"),
					Li("Bash, terminal, command line, shell, etc.")
				])
			]),

			// Security & Development Operations
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-xl font-bold" }, "Security & Development Operations"),
				Ul({ class: "list-disc pl-6 space-y-1" }, [
					Li("Agile, Scrum, Kanban, Waterfall, etc."),
					Li("GitOps"),
					Li("DevOps"),
					Li("SecOps")
				])
			]),

			// Development Platforms
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-xl font-bold" }, "Development Platforms"),
				Ul({ class: "list-disc pl-6 space-y-1" }, [
					Li("Browser features, APIs, and limitations"),
					Li("Mobile using React Native, Flutter, etc."),
					Li("Desktop using .NET and Electron or other frameworks"),
					Li("Cloud: AWS, Azure, Google Cloud, etc."),
					Li("Serverless: AWS Lambda, Azure Functions, Google Cloud Functions, etc.")
				])
			]),

			// Data Structures and Algorithms
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-xl font-bold" }, "Data Structures and Algorithms"),
				Ul({ class: "list-disc pl-6 space-y-1" }, [
					Li("Data Structures: built-in data types"),
					Li("Big O Notation, Scaling, Performance"),
					Li("Algorithms: Sorting, etc."),
					Li("Dynamic Programming")
				])
			]),

			// System Design
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-xl font-bold" }, "System Design"),
				Ul({ class: "list-disc pl-6 space-y-1" }, [
					Li("Architectures & Design Patterns"),
					Li("Cloud architecture"),
					Li("Domain-driven Design"),
					Li("SOA (Service Oriented Architecture)"),
					Li("System Architecture"),
					Li("Layered Architecture (Monolith)"),
					Li("Polylith Architecture"),
					Li("Modular Monolith Architecture"),
					Li("Vertical Slice Architecture"),
					Li("Hexagonal Architecture"),
					Li("Onion Architecture"),
					Li("Clean Architecture")
				])
			]),

			// Distributed Systems
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-xl font-bold" }, "Distributed Systems"),
				Ul({ class: "list-disc pl-6 space-y-1" }, [
					Li("Microservices"),
					Li("Serverless"),
					Li("Event-Driven Design"),
					Li("Apache Kafka, RabbitMQ, etc."),
					Li("Message Queues, Event Queues, Event Bus, Pub-Sub, etc."),
					Li("CQRS, Event Sourcing, etc."),
					Li("Cloud Services, Caching & Networking"),
					Li("Consistency, Eventual, etc."),
					Li("AWS, Azure, Google Cloud"),
					Li("Caching: Redis, Server Caching, Client-side Caching, JavaScript Cache API"),
					Li("Nginx, Apache, Proxy, Reverse Proxy, Load Balancers"),
					Li("Kubernetes")
				])
			])
		]
	)
);

export default DeveloperSkillsPage;
