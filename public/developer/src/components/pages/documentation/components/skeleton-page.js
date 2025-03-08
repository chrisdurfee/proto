import { Div, P, Span } from '@base-framework/atoms';
import { Component, Jot } from "@base-framework/base";
import { Button, Skeleton } from "@base-framework/ui/atoms";
import { Avatar } from "@base-framework/ui/molecules";
import { DocSection } from "../../../molecules/doc-section.js";
import { DocPage } from '../../doc-page.js';

/**
 * LoadingAvatar Component
 *
 * This component displays a skeleton as a placeholder
 * for an avatar and text while loading. After 3 seconds,
 * it switches to the actual avatar and text content.
 *
 * @type {typeof Component} LoadingAvatar
 */
const LoadingAvatar = Jot(
{
    state: {
        loaded: false
    },

    render()
    {
        return Div({ class: 'flex flex-auto flex-col max-w-[350px]' }, [
            Div({ class: 'my-8' }, [
                // @ts-ignore
                Button({ variant: 'outline', click: () => this.state.toggle('loaded') }, 'Toggle Loaded')
            ]),
            Div({ class: 'flex items-center bg-card rounded-md border shadow-md w-full max-w-md' }, [
                Div({
                    class: 'flex flex-auto gap-4 p-4',
                    onState: ['loaded', (loaded) =>
                    {
                        return loaded
                        ? [
                            Avatar({ src: 'https://github.com/shadcn.png', alt: '@shadcn', fallbackText: 'CN', size: 'md' }),
                            Div({ class: 'flex flex-auto flex-col' }, [
                                Span({ class: 'text-lg font-semibold text-foreground' }, 'Leslie Alexander'),
                                P({ class: 'text-sm text-muted-foreground m-0' }, 'leslie.alexander@example.com')
                            ])
                            ]
                        : [
                            Skeleton({ shape: 'circle', width: 'w-12', height: 'h-12' }), // Skeleton avatar
                            Div({ class: 'flex flex-auto flex-col gap-2' }, [
                                Skeleton({ width: 'w-3/4', height: 'h-6' }), // Skeleton for name
                                Skeleton({ width: 'w-1/2', height: 'h-4' })  // Skeleton for email
                            ])
                        ];
                    }
                ]})
            ])
        ]);
    }
});

/**
 * SkeletonPage
 *
 * This will create a dialog page.
 *
 * @returns {DocPage}
 */
export const SkeletonPage = () => (
	DocPage(
        {
            title: 'Skeletons',
            description: 'Skeletons are components that are used to show a placeholder for content that is loading.',
        },
        [
            DocSection({
                title: 'Usage',
                description: 'The skeleton component is used to show a placeholder for content that is loading.',
                preview: [
                    Div({ class: 'flex flex-auto space-x-4 w-full max-w-64' }, [
                        Skeleton({ shape: 'circle', width: 'w-10', height: 'h-10' }), // Circle skeleton for avatar
                        Div({ class: 'flex flex-auto flex-col space-y-2' }, [
                            Skeleton({ width: 'w-3/4', height: 'h-4' }), // Long rectangle for main text
                            Skeleton({ width: 'w-1/2', height: 'h-4' })  // Shorter rectangle for subtext
                        ])
                    ])
                ],
                code: `import {  Skeleton  } from "@base-framework/ui/atoms";
import { Div } from '@base-framework/atoms';

Div({ class: 'flex flex-auto space-x-4 w-full max-w-64' }, [
    Skeleton({ shape: 'circle', width: 'w-10', height: 'h-10' }), // Circle skeleton for avatar
    Div({ class: 'flex flex-auto flex-col space-y-2' }, [
        Skeleton({ width: 'w-3/4', height: 'h-4' }), // Long rectangle for main text
        Skeleton({ width: 'w-1/2', height: 'h-4' })  // Shorter rectangle for subtext
    ])
])`
            }),

            DocSection({
                title: 'Skeleton Post',
                description: 'This is a skeleton post.',
                preview: [
                    Div({ class: 'space-y-4 flex flex-auto flex-col w-full max-w-64' }, [
                        // Large rectangle for image or main content
                        Skeleton({ width: 'w-full', height: 'h-32', shape: 'rectangle' }),

                        // Text line placeholders
                        Div({ class: 'space-y-2' }, [
                            Skeleton({ width: 'w-3/4', height: 'h-4' }), // Longer text line
                            Skeleton({ width: 'w-1/2', height: 'h-4' })  // Shorter text line
                        ])
                    ])
                ],
                code: `import {  Skeleton  } from "@base-framework/ui/atoms";
import { Div } from '@base-framework/atoms';

Div({ class: 'space-y-4 flex flex-auto flex-col space-x-4 w-full max-w-64' }, [
    // Large rectangle for image or main content
    Skeleton({ width: 'w-full', height: 'h-32', shape: 'rectangle' }),

    // Text line placeholders
    Div({ class: 'space-y-2' }, [
        Skeleton({ width: 'w-3/4', height: 'h-4' }), // Longer text line
        Skeleton({ width: 'w-1/2', height: 'h-4' })  // Shorter text line
    ])
])`
            }),

            DocSection({
                title: 'Skeleton with Avatar Loading',
                description: 'This example demonstrates a skeleton loading animation that transitions to an avatar and user info after 3 seconds.',
                preview: [
                    new LoadingAvatar()
                ],
                code: `import {  Skeleton  } from "@base-framework/ui/atoms";
import {  Avatar  } from "@base-framework/ui/molecules";
import { Jot } from "@base-framework/base";
import { Div, P, Span } from "@base-framework/atoms";
import {  Button  } from "@base-framework/ui/atoms";

/**
 * LoadingAvatar Component
 *
 * This component displays a skeleton as a placeholder
 * for an avatar and text while loading. After 3 seconds,
 * it switches to the actual avatar and text content.
 */
const LoadingAvatar = Jot(
{
    state: {
        loaded: false
    },

    render()
    {
        return Div({ class: 'flex flex-auto flex-col max-w-[350px]' }, [
            Div({ class: 'my-8' }, [
                Button({ variant: 'outline', click: () => this.state.toggle('loaded') }, 'Toggle Loaded')
            ]),
            Div({ class: 'flex items-center bg-card rounded-md border shadow-md w-full max-w-md' }, [
                Div({
                    class: 'flex flex-auto gap-4 p-4',
                    onState: ['loaded', (loaded) =>
                    {
                        return loaded
                        ? [
                            Avatar({ src: 'https://github.com/shadcn.png', alt: '@shadcn', fallbackText: 'CN', size: 'md' }),
                            Div({ class: 'flex flex-auto flex-col' }, [
                                Span({ class: 'text-lg font-semibold text-foreground' }, 'Leslie Alexander'),
                                P({ class: 'text-sm text-muted-foreground m-0' }, 'leslie.alexander@example.com')
                            ])
                            ]
                        : [
                            Skeleton({ shape: 'circle', width: 'w-12', height: 'h-12' }), // Skeleton avatar
                            Div({ class: 'flex flex-auto flex-col gap-2' }, [
                                Skeleton({ width: 'w-3/4', height: 'h-6' }), // Skeleton for name
                                Skeleton({ width: 'w-1/2', height: 'h-4' })  // Skeleton for email
                            ])
                        ];
                    }
                ]})
            ])
        ]);
    }
});

LoadingAvatar();`
            })
        ]
    )
);

export default SkeletonPage;