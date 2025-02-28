import { H4, Li, Section, Ul } from "@base-framework/atoms";
import { DocPage } from "../../../../../doc-page.js";

/**
 * ProductSecurityPage
 *
 * This page outlines the product security measures employed by our company.
 *
 * @returns {DocPage}
 */
export const ProductSecurityPage = () => (
	DocPage(
		{
			title: "Product Security",
			description:
				"We are committed to ensuring the highest level of security for Essentials and other internal and external services. We implement a range of best practices to secure our servers, application, and client data. This document outlines some of the key methods we employ to maintain a secure environment."
		},
		[
			// Hosting
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-2xl font-bold" }, "Hosting"),
				Ul({ class: "list-disc pl-6 space-y-1 text-muted-foreground" }, [
					Li("We utilize secure, cloud-hosted servers that adhere to industry best practices for premises security and server access."),
					Li("Access to specific resources is restricted through white-listing."),
					Li("Regular security scans ensure that our systems remain secure."),
					Li("Our platform uses an HTTPS connection with a TLS 1.3 certificate, the latest available version, to encrypt all data sent to and from the application."),
					Li("We enforce secure connections at the server level.")
				])
			]),

			// Servers
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-2xl font-bold" }, "Servers"),
				Ul({ class: "list-disc pl-6 space-y-1 text-muted-foreground" }, [
					Li("Our application is prevented from being nested in iframes."),
					Li("We implement XSS-Protection headers to counteract cross-site scripting."),
					Li("Strict-Transport-Security headers are in place to mitigate man-in-the-middle attacks."),
					Li("Server details are restricted from being shared in server request responses."),
					Li("Our technology stack is regularly updated to incorporate the latest patches and supported library versions."),
					Li("Security protocols are in place to restrict access to sensitive files on our servers.")
				])
			]),

			// Platform
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-2xl font-bold" }, "Platform"),
				Ul({ class: "list-disc pl-6 space-y-1 text-muted-foreground" }, [
					Li("User authentication is mandatory, with all users required to log in using a username and password that meet HIPAA recommendations."),
					Li("Cookies set by the application can only be used over an HTTP secure connection, adhering to the strict same-site policy."),
					Li("We employ rate limiting and brute force prevention measures to combat malicious activities during login attempts."),
					Li("CSRF prevention mechanisms are used to block unauthorized actions."),
					Li("Our platform API utilizes a secure gating and policy system for per-action authentication, which occurs on all requests to the app."),
					Li("Malicious file uploads are automatically blocked by our server-side framework."),
					Li("Statement param-binding is used to prepare all data inserted or updated in databases, preventing SQL injection."),
					Li("Sensitive data is encrypted using industry best practices and can only be decrypted by our application using an authorized account with access rights.")
				])
			]),

			// Development
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-2xl font-bold" }, "Development"),
				Ul({ class: "list-disc pl-6 space-y-1 text-muted-foreground" }, [
					Li("System Engineers and our Security Manager provide training for our teams, ensuring secure and resilient system development."),
					Li("Regular updates on new security topics are provided to the teams, along with encouragement to continue learning."),
					Li("We leverage tools, libraries, and best practices to create secure applications."),
					Li("We conduct monthly organizational training on new security issues and perform security testing, including phishing exercises, to verify the effectiveness of our training.")
				])
			])
		]
	)
);

export default ProductSecurityPage;
