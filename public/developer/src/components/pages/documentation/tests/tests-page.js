import { Code, H4, P, Pre, Section } from "@base-framework/atoms";
import { Atom } from "@base-framework/base";
import { DocPage } from "../../doc-page.js";

/**
 * CodeBlock
 *
 * Creates a code block with copy-to-clipboard functionality.
 *
 * @param {object} props
 * @param {object} children
 * @returns {object}
 */
const CodeBlock = Atom((props, children) => (
	Pre(
		{
			...props,
			class: `flex p-4 max-h-[650px] max-w-[1024px] overflow-x-auto
                     rounded-lg border bg-muted whitespace-break-spaces
                     break-all cursor-pointer mt-4 ${props.class}`
		},
		[
			Code(
				{
					class: "font-mono flex-auto text-sm text-wrap",
					click: () => {
						navigator.clipboard.writeText(children[0].textContent);
						// @ts-ignore
						app.notify({
							title: "Code copied",
							description: "The code has been copied to your clipboard.",
							icon: null
						});
					}
				},
				children
			)
		]
	)
));

/**
 * TestsPage
 *
 * This page explains how Proto uses PHPUnit for testing.
 * It covers naming conventions for tests, setup and teardown methods, and guidelines for naming test methods.
 *
 * @returns {DocPage}
 */
export const TestsPage = () =>
	DocPage(
		{
			title: 'Tests',
			description: 'Learn how to write tests in Proto using the PHPUnit library.'
		},
		[
			// Overview
			Section({ class: "space-y-4" }, [
				H4({ class: "text-lg font-bold" }, "Overview"),
				P(
					{ class: "text-muted-foreground" },
					`Proto uses the PHPUnit library to perform unit testing.
                    This allows you to verify that your code behaves as expected and to catch regressions early.`
				)
			]),

			// Naming
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-lg font-bold" }, "Naming"),
				P(
					{ class: "text-muted-foreground" },
					`The name of a test should always be singular and end with "Test". For example:`
				),
				CodeBlock(
`<?php
declare(strict_types=1);
namespace Tests\\Unit;

use Tests\\Test;

class ExampleTest extends Test
{
    protected function setUp(): void
    {
        // Setup code before each test
    }

    protected function tearDown(): void
    {
        // Cleanup code after each test
    }
}`
				)
			]),

			// Set-Up
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-lg font-bold" }, "Set-Up"),
				P(
					{ class: "text-muted-foreground" },
					`The setUp() method is called before each test is run.
                    Use it to initialize any resources or state required for your tests.`
				),
				CodeBlock(
`protected function setUp(): void
{
    // Execute code to set up the test environment
}`
				)
			]),

			// Tear-Down
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-lg font-bold" }, "Tear-Down"),
				P(
					{ class: "text-muted-foreground" },
					`The tearDown() method is called after each test completes.
                    Use it to clean up any resources or reset state.`
				),
				CodeBlock(
`protected function tearDown(): void
{
    // Execute code to clean up after tests
}`
				)
			]),

			// Test Method Names
			Section({ class: "space-y-4 mt-12" }, [
				H4({ class: "text-lg font-bold" }, "Test Method Names"),
				P(
					{ class: "text-muted-foreground" },
					`Test method names should begin with "test" followed by the action being tested. For example:`
				),
				CodeBlock(
`public function testClassHasAttribute(): void
{
    $this->assertClassHasAttribute('foo', stdClass::class);
}`
				),
				P(
					{ class: "text-muted-foreground" },
					`Following these conventions helps ensure that tests are easily discoverable and their purpose is clear.`
				)
			])
		]
	);

export default TestsPage;
