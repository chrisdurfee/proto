import{a2 as a,a0 as o,G as e,s as r,u as c,N as l,o as d}from"./index-DM3KSgk2.js";import{D as i}from"./doc-page-DoAFO_jW.js";import"./sidebar-menu-page-D_2zNFuZ-DKHVgHxo.js";const t=r((s,n)=>c({...s,class:`flex p-4 max-h-[650px] max-w-[1024px] overflow-x-auto
					 rounded-lg border bg-muted whitespace-break-spaces
					 break-all cursor-pointer mt-4 ${s.class}`},[l({class:"font-mono flex-auto text-sm text-wrap",click:()=>{navigator.clipboard.writeText(n[0].textContent),app.notify({title:"Code copied",description:"The code has been copied to your clipboard.",icon:d.clipboard.checked})}},n)])),h=()=>i({title:"Events System",description:"Learn how Proto supports server event listeners for storage actions and custom events."},[a({class:"space-y-4"},[o({class:"text-lg font-bold"},"Overview"),e({class:"text-muted-foreground"},"Proto supports server event listeners that can be set up and published to react to changes in your application.\n					The events class is available as `Proto\\Events\\Events` and allows you to register callbacks for various events.")]),a({class:"space-y-4 mt-12"},[o({class:"text-lg font-bold"},"Storage Events"),e({class:"text-muted-foreground"},"The storage layer automatically publishes events for all actions performed via the\n					`Proto\\Storages\\StorageProxy` that models use to interface with the storage layer.\n					This enables you to listen for storage events as they occur."),e({class:"text-muted-foreground"},"To register an event, call the `on` method with the event name and a callback. The storage event name is formed by the model name and method name separated by a colon."),t(`<?php declare(strict_types=1);
namespace Proto\\Events;

Events::on('Ticket:add', function($payload) {
    /**
     * $payload includes:
     * - args: the arguments passed to the storage method.
     * - data: the data passed or retrieved from the database.
     */
});`),e({class:"text-muted-foreground"},"To manually publish an event, call the `update` method:"),t(`<?php declare(strict_types=1);
namespace Proto\\Events;

Events::update('Ticket:add', (object)[
    'args'  => 'the args',
    'model' => 'the model data'
]);`),e({class:"text-muted-foreground"},`If you wish to listen to general storage events without specifying a model or method,
					Proto automatically publishes a "Storage" event on every update:`),t(`<?php declare(strict_types=1);
namespace Proto\\Events;

Events::on('Storage', function($payload) {
    /**
     * $payload is an object containing:
     * - target: the model name,
     * - method: the method name,
     * - data: the model data.
     */
});`)]),a({class:"space-y-4 mt-12"},[o({class:"text-lg font-bold"},"Custom Events"),e({class:"text-muted-foreground"},`In addition to storage events, Proto supports custom events.
					You can register and publish custom events to allow your application to react to specific changes.`),t(`<?php declare(strict_types=1);
namespace Proto\\Events;

Events::on('CustomEvent', function($payload) {
    // Handle custom event logic here.
});

Events::update('CustomEvent', (object)[
    'custom' => 'custom data'
]);`)])]);export{h as EventsPage,h as default};
