import { Div, H4, P, Span } from "@base-framework/atoms";
import { Avatar, StaticStatusIndicator, StatusIndicator } from "@base-framework/ui/molecules";
import { DocSection } from "../../../molecules/doc-section.js";
import { DocPage } from '../../doc-page.js';

/**
 * AvatarPage
 *
 * This will create an avatar page.
 *
 * @returns {DocPage}
 */
export const AvatarPage = () => (
    DocPage(
        {
            title: 'Avatars',
            description: 'Avatars are used to represent users or objects visually, with customizable sizes and optional status indicators.',
        },
        [
            // Basic Avatars by Size
            DocSection({
                title: 'Extra Small Avatar (xs)',
                description: 'An extra small avatar for compact spaces.',
                preview: [
                    Avatar({ src: 'https://github.com/shadcn.png', alt: '@shadcn', fallbackText: 'CN', size: 'xs' })
                ],
                code: `Avatar({ src: 'https://github.com/shadcn.png', alt: '@shadcn', fallbackText: 'CN', size: 'xs' })`
            }),

            H4({ class: 'text-2xl font-semibold text-foreground mt-8' }, 'Avatar Fallbacks'),
            P({ class: 'text-sm text-muted-foreground mb-4' }, `The avatar fallback can be bound to a watcher for dynamic content using the 'watcherFallback' property. The fallback and watcher fallback will convert a name to initials if not set as a two letter string.`),

            DocSection({
                title: 'Small Avatar (sm)',
                description: 'A small avatar suitable for lists and compact layouts.',
                preview: [
                    Avatar({ src: 'https://github.com/shadcn.png', alt: '@shadcn', fallbackText: 'CN', size: 'sm' })
                ],
                code: `Avatar({ src: 'https://github.com/shadcn.png', alt: '@shadcn', fallbackText: 'CN', size: 'sm' })`
            }),

            DocSection({
                title: 'Medium Avatar (md)',
                description: 'A medium-sized avatar, often used for standard profile images.',
                preview: [
                    Avatar({ src: 'https://github.com/shadcn.png', alt: '@shadcn', fallbackText: 'CN', size: 'md' })
                ],
                code: `Avatar({ src: 'https://github.com/shadcn.png', alt: '@shadcn', fallbackText: 'CN', size: 'md' })`
            }),

            DocSection({
                title: 'Large Avatar (lg)',
                description: 'A large avatar for prominent profile displays.',
                preview: [
                    Avatar({ src: 'https://github.com/shadcn.png', alt: '@shadcn', fallbackText: 'CN', size: 'lg' })
                ],
                code: `Avatar({ src: 'https://github.com/shadcn.png', alt: '@shadcn', fallbackText: 'CN', size: 'lg' })`
            }),

            DocSection({
                title: 'Extra Large Avatar (xl)',
                description: 'An extra-large avatar for detailed user profile sections.',
                preview: [
                    Avatar({ src: 'https://github.com/shadcn.png', alt: '@shadcn', fallbackText: 'CN', size: 'xl' })
                ],
                code: `Avatar({ src: 'https://github.com/shadcn.png', alt: '@shadcn', fallbackText: 'CN', size: 'xl' })`
            }),

            DocSection({
                title: '2x Extra Large Avatar (2xl)',
                description: 'A double extra-large avatar for large displays.',
                preview: [
                    Avatar({ src: 'https://github.com/shadcn.png', alt: '@shadcn', fallbackText: 'CN', size: '2xl' })
                ],
                code: `Avatar({ src: 'https://github.com/shadcn.png', alt: '@shadcn', fallbackText: 'CN', size: '2xl' })`
            }),

            DocSection({
                title: '3x Extra Large Avatar (3xl)',
                description: 'A triple extra-large avatar for the largest profile displays.',
                preview: [
                    Avatar({ src: 'https://github.com/shadcn.png', alt: '@shadcn', fallbackText: 'CN', size: '3xl' })
                ],
                code: `Avatar({ src: 'https://github.com/shadcn.png', alt: '@shadcn', fallbackText: 'CN', size: '3xl' })`
            }),

            // Avatar with Name and Subtitle
            DocSection({
                title: 'Avatar with Name and Subtitle',
                description: 'Display an avatar alongside a name and subtitle for user identification.',
                preview: [
                    Div({ class: 'flex items-center gap-4 p-4 bg-card rounded-md border shadow-md' }, [
                        Avatar({ src: 'https://github.com/shadcn.png', alt: '@shadcn', fallbackText: 'CN', size: 'lg' }),
                        Div({}, [
                            Span({ class: 'text-lg font-semibold text-foreground' }, 'Leslie Alexander'),
                            P({ class: 'text-sm text-muted-foreground' }, 'leslie.alexander@example.com')
                        ])
                    ])
                ],
                code: `Div({ class: 'flex items-center gap-4 p-4 bg-card rounded-md border shadow-md' }, [
    Avatar({ src: 'https://github.com/shadcn.png', alt: '@shadcn', fallbackText: 'CN', size: 'lg' }),
    Div({}, [
        Span({ class: 'text-lg font-semibold text-foreground' }, 'Leslie Alexander'),
        P({ class: 'text-sm text-muted-foreground' }, 'leslie.alexander@example.com')
    ])
])`
            }),

            // Avatar with Online Status Indicator
            DocSection({
                title: 'Avatar with Online Status',
                description: 'Displays an avatar with a small online status indicator.',
                preview: [
                    Div({ class: 'relative flex items-center gap-4 p-4 bg-card rounded-md border shadow-md' }, [
                        Div({ class: 'relative' }, [
                            Avatar({ src: 'https://github.com/shadcn.png', alt: '@shadcn', fallbackText: 'CN', size: 'lg' }),
                            Div({ class: 'absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 rounded-full' }) // Online indicator
                        ]),
                        Div({}, [
                            Span({ class: 'text-lg font-semibold text-foreground' }, 'Leslie Alexander'),
                            P({ class: 'text-sm text-muted-foreground' }, 'leslie.alexander@example.com')
                        ])
                    ])
                ],
                code: `Div({ class: 'relative flex items-center gap-4 p-4 bg-card rounded-md border shadow-md' }, [
    Div({ class: 'relative' }, [
        Avatar({ src: 'https://github.com/shadcn.png', alt: '@shadcn', fallbackText: 'CN', size: 'lg' }),
        Div({ class: 'absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 rounded-full' }) // Online indicator
    ]),
    Div({}, [
        Span({ class: 'text-lg font-semibold text-foreground' }, 'Leslie Alexander'),
        P({ class: 'text-sm text-muted-foreground' }, 'leslie.alexander@example.com')
    ])
])`
            }),

            // Status Indicator
            DocSection({
                title: 'Status Indicator',
                description: 'A small status indicator for online, offline, busy, or away statuses. This will bind to the data or state status of the parent component.',
                preview: [
                    Div({
                        class: 'relative',
                        addState()
                        {
                            return {
                                status: 'online'
                            };
                        }
                    }, [
                        StatusIndicator()
                    ])
                ],
                code: `import { Div, H4, P, Span } from "@base-framework/atoms";
import {  StatusIndicator  } from "@base-framework/ui/molecules";

Div({
    class: 'relative',
    addState()
    {
        return {
            status: 'online'
        };
    }
}, [
    StatusIndicator()
])`
            }),

            DocSection({
                title: 'Static Status Indicator',
                description: 'A small status indicator for online, offline, busy, or away statuses.',
                preview: [
                    Div({ class: 'relative' }, [
                        StaticStatusIndicator('online')
                    ])
                ],
                code: `import { Div, H4, P, Span } from "@base-framework/atoms";
import {  StaticStatusIndicator  } from "@base-framework/ui/molecules";

Div({ class: 'relative' }, [
    StaticStatusIndicator('online')
])`
            })
        ]
    )
);

export default AvatarPage;