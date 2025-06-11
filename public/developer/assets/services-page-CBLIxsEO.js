import{a2 as t,a0 as s,G as e,s as i,u as n,N as c}from"./index-DM3KSgk2.js";import{D as l}from"./doc-page-DoAFO_jW.js";import"./sidebar-menu-page-D_2zNFuZ-DKHVgHxo.js";const a=i((o,r)=>n({...o,class:`flex p-4 max-h-[650px] max-w-[1024px] overflow-x-auto
					 rounded-lg border bg-muted whitespace-break-spaces
					 break-all cursor-pointer mt-4 ${o.class}`},[c({class:"font-mono flex-auto text-sm text-wrap",click:()=>{navigator.clipboard.writeText(r[0].textContent),app.notify({title:"Code copied",description:"The code has been copied to your clipboard.",icon:null})}},r)])),m=()=>l({title:"Service Providers",description:"Learn how to create, register, and activate service providers in Proto."},[t({class:"space-y-4"},[s({class:"text-lg font-bold"},"Overview"),e({class:"text-muted-foreground"},`Service providers in Proto are self-contained and provide additional functionality that is loaded immediately after the framework boots.
					They are registered in your configuration file (typically within common/Config) under the "services" key, for example:`),a(`"services": [
    "Example\\ExampleService",
    "Example\\Parent\\ProductionService"
]`),e({class:"text-muted-foreground"},"Once registered, service providers can listen for events, especially from the storage layer, and set up any global functionality your application needs.")]),t({class:"space-y-4 mt-12"},[s({class:"text-lg font-bold"},"Naming"),e({class:"text-muted-foreground"},'The name of a service should always be singular and followed by "Service". For example:'),a(`<?php
namespace Common\\Services\\Providers;

use Proto\\Providers\\ServiceProvider as Service;

class ExampleService extends Service
{
    protected function addEvents()
    {
        // Register events here
    }

    public function activate()
    {
        // Perform actions on framework activation
    }
}`)]),t({class:"space-y-4 mt-12"},[s({class:"text-lg font-bold"},"Activation"),e({class:"text-muted-foreground"},`Service providers are activated when the framework boots. This allows service providers to register any actions or listeners they need to be available
					immediately as the application starts. For example:`),a(`// In a service class
public function activate()
{
    // Perform setup tasks, such as initializing components or registering listeners.
}`)]),t({class:"space-y-4 mt-12"},[s({class:"text-lg font-bold"},"Events"),e({class:"text-muted-foreground"},`Service providers can also register events to respond to various actions, such as storage events.
					Within your service, use the inherited event method to set up event listeners. For example:`),a(`// In a service class
protected function addEvents()
{
    $this->event('Ticket:add', function($ticket) {
        // Handle the event when a ticket is added.
    });
}`)])]);export{m as ServicesPage,m as default};
