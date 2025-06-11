import{a2 as t,a0 as s,G as e,s as n,u as c,N as l}from"./index-DM3KSgk2.js";import{D as i}from"./doc-page-DoAFO_jW.js";import"./sidebar-menu-page-D_2zNFuZ-DKHVgHxo.js";const o=n((a,r)=>c({...a,class:`flex p-4 max-h-[650px] max-w-[1024px] overflow-x-auto
                     rounded-lg border bg-muted whitespace-break-spaces
                     break-all cursor-pointer mt-4 ${a.class}`},[l({class:"font-mono flex-auto text-sm text-wrap",click:()=>{navigator.clipboard.writeText(r[0].textContent),app.notify({title:"Code copied",description:"The code has been copied to your clipboard.",icon:null})}},r)])),f=()=>i({title:"Tests",description:"Learn how to write tests in Proto using the PHPUnit library."},[t({class:"space-y-4"},[s({class:"text-lg font-bold"},"Overview"),e({class:"text-muted-foreground"},`Proto uses the PHPUnit library to perform unit testing.
                    This allows you to verify that your code behaves as expected and to catch regressions early.`)]),t({class:"space-y-4 mt-12"},[s({class:"text-lg font-bold"},"Naming"),e({class:"text-muted-foreground"},'The name of a test should always be singular and end with "Test". For example:'),o(`<?php
declare(strict_types=1);
namespace Module\\User\\Tests\\Unit;

use Proto\\Tests\\Test;

class ExampleTest extends Test
{
    protected function setUp(): void
    {
        // Setup code before each test
		parent::setUp();
    }

    protected function tearDown(): void
    {
        // Cleanup code after each test
		parent::tearDown();
    }
}`)]),t({class:"space-y-4 mt-12"},[s({class:"text-lg font-bold"},"Set-Up"),e({class:"text-muted-foreground"},`The setUp() method is called before each test is run.
                    Use it to initialize any resources or state required for your tests.`),o(`protected function setUp(): void
{
    // Execute code to set up the test environment
	parent::setUp();
}`)]),t({class:"space-y-4 mt-12"},[s({class:"text-lg font-bold"},"Tear-Down"),e({class:"text-muted-foreground"},`The tearDown() method is called after each test completes.
                    Use it to clean up any resources or reset state.`),o(`protected function tearDown(): void
{
    // Execute code to clean up after tests
	parent::tearDown();
}`)]),t({class:"space-y-4 mt-12"},[s({class:"text-lg font-bold"},"Test Method Names"),e({class:"text-muted-foreground"},'Test method names should begin with "test" followed by the action being tested. For example:'),o(`public function testClassHasAttribute(): void
{
    $this->assertClassHasAttribute('foo', stdClass::class);
}`),e({class:"text-muted-foreground"},"Following these conventions helps ensure that tests are easily discoverable and their purpose is clear.")])]);export{f as TestsPage,f as default};
