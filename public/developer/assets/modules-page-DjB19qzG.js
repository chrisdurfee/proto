import{a2 as e,a0 as t,G as a,s as d,u as n,N as i}from"./index-DD5ZlTX4.js";import{D as r}from"./doc-page-DFkqm2Y0.js";import"./sidebar-menu-page-D_2zNFuZ-DJNCgvAz.js";const o=d((s,l)=>n({...s,class:`flex p-4 max-h-[650px] max-w-[1024px] overflow-x-auto
					 rounded-lg border bg-muted whitespace-break-spaces
					 break-all cursor-pointer mt-4 ${s.class}`},[i({class:"font-mono flex-auto text-sm text-wrap",click:()=>{navigator.clipboard.writeText(l[0].textContent),app.notify({title:"Code copied",description:"The code has been copied to your clipboard.",icon:null})}},l)])),p=()=>r({title:"Modules",description:"Learn how to create, manage, and register modules in Proto."},[e({class:"space-y-4"},[t({class:"text-lg font-bold"},"Overview"),a({class:"text-muted-foreground"},`Each feature or domain of your application should be developed as a separate module.
					Modules are self-contained units that encapsulate APIs, controllers, models, and gateways.
					They can interact with other registered modules when necessary.`)]),e({class:"space-y-4 mt-12"},[t({class:"text-lg font-bold"},"Module Folder Structure"),a({class:"text-muted-foreground"},`All modules reside in their own folders inside the modules directory.
					Modules can be generated using the developer code generator, making it easy to add new features.`)]),e({class:"space-y-4 mt-12"},[t({class:"text-lg font-bold"},"Module Gateway"),a({class:"text-muted-foreground"},`Modules can include a gateway file within a gateway subfolder. The gateway provides
					a public interface for accessing module functionality from other modules. Gateways can also support versioning
					to allow updates while maintaining backward compatibility.`),o(`<?php declare(strict_types=1);
namespace Modules\\Example\\Gateway;

/**
 * Gateway
 *
 * This will handle the example module gateway.
 * Call it from another module like:
 * modules()->example()->add();
 * To use versioned methods:
 * modules()->example()->v1()->add();
 * modules()->example()->v2()->add();
 */
class Gateway
{
    public function add(): void
    {
        // Implementation for adding an example.
    }

    public function v1(): V1\\Gateway
    {
        return new \\Modules\\Example\\Gateway\\V1\\Gateway();
    }

    public function v2(): V2\\Gateway
    {
        return new \\Modules\\Example\\Gateway\\V2\\Gateway();
    }
}`)]),e({class:"space-y-4 mt-12"},[t({class:"text-lg font-bold"},"Example Module"),a({class:"text-muted-foreground"},`Below is an example module that demonstrates how to encapsulate a feature within a module.
					The module extends the base Module class, sets up configurations, and registers events.`),o(`<?php declare(strict_types=1);
namespace Modules\\Example;

use Proto\\Module\\Module;

/**
 * ExampleModule
 *
 * This module is an example of how to create a module in the Proto framework.
 */
class ExampleModule extends Module
{
    public function activate(): void
    {
        $this->setConfigs();
    }

    private function setConfigs(): void
    {
        setEnv('settingName', 'value');
    }

    protected function addEvents(): void
    {
        // Add an event for when a ticket is added.
        $this->event('Ticket:add', fn($ticket): void => var_dump($ticket));
    }
}`)]),e({class:"space-y-4 mt-12"},[t({class:"text-lg font-bold"},"Module Registration"),a({class:"text-muted-foreground"},`For a module to be valid and loaded, it must be registered in your configuration file
					(e.g. in the common .env file) under the "modules" key. For example:`),o(`"modules": [
    "Example\\ExampleModule",
    "Product\\ProductModule",
    "User\\UserModule"
]`)])]);export{p as ModulesPage,p as default};
