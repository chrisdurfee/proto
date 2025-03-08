import { H4, P } from "@base-framework/atoms";
import { Card } from "@base-framework/ui/atoms";
import { DocSection } from "../../../../molecules/doc-section.js";
import { DocPage } from '../../../doc-page.js';
import AccountCard from "./examples/account-card.js";
import CookieSettingsCard from "./examples/cookie-settings-card.js";
import DateSelectCard from "./examples/date-select-card.js";
import PaymentCard from "./examples/payment-card.js";
import ProjectCard from "./examples/project-card.js";
import ReportIssueCard from "./examples/report-issue-card.js";
import ShareDocumentCard from "./examples/share-document-card.js";
import TeamMemberCard from "./examples/team-member-card.js";

/**
 * DialogPage
 *
 * This will create a dialog page.
 *
 * @returns {DocPage}
 */
export const CardPage = () => (
	DocPage(
        {
            title: 'Cards',
            description: 'Cards are a great way to display content in a concise and organized manner.',
        },
        [
            DocSection({
                title: 'Usage',
                description: 'Import the card atom and use it in your components.',
                preview: [
                    Card()
                ],
                code: `import {  Button  } from "@base-framework/ui/atoms";

Card()`
            }),

            H4({ class: 'text-lg font-bold' }, 'Examples'),
            P({ class: 'text-muted-foreground' }, 'This is a list of examples of how to use the card component in your application.'),

            DocSection({
                title: 'Account Card',
                preview: [
                    AccountCard()
                ],
                code: `import AccountCard from "./examples/account-card.js";

AccountCard()`
            }),

            DocSection({
                title: 'Paymnet Card',
                preview: [
                    PaymentCard()
                ],
                code: `import AccountCard from "./examples/payment-card.js";

PaymentCard()`
            }),

            DocSection({
                title: 'Team Mmeber Card',
                preview: [
                    TeamMemberCard({
                        members: [
                            {
                                name: 'Leslie Alexander',
                                email: 'leslie.alexander@example.com',
                                image: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80',
                                role: 'owner',
                                lastSeen: '2023-01-23T13:23Z',
                                status: 'offline'
                            },
                            {
                                name: 'Michael Foster',
                                email: 'michael.foster@example.com',
                                image: 'https://images.unsplash.com/photo-1519244703995-f4e0f30006d5?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80',
                                role: 'member',
                                lastSeen: '2023-01-23T13:23Z',
                                status: 'offline'
                            },
                            {
                                name: 'Dries Vincent',
                                email: 'dries.vincent@example.com',
                                image: 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80',
                                role: 'owner',
                                status: 'online'
                            }
                        ]
                    })
                ],
                code: `import AccountCard from "./examples/team-member-card.js";

TeamMemberCard()`
            }),

            DocSection({
                title: 'Share Document Card',
                preview: [
                    ShareDocumentCard({
                        link: 'http://example.com/link/to/document',
                        people: [
                            {
                                name: 'Leslie Alexander',
                                email: 'leslie.alexander@example.com',
                                image: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80',
                                role: 'owner',
                                lastSeen: '2023-01-23T13:23Z',
                                status: 'offline'
                            },
                            {
                                name: 'Michael Foster',
                                email: 'michael.foster@example.com',
                                image: 'https://images.unsplash.com/photo-1519244703995-f4e0f30006d5?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80',
                                role: 'member',
                                lastSeen: '2023-01-23T13:23Z',
                                status: 'offline'
                            },
                            {
                                name: 'Dries Vincent',
                                email: 'dries.vincent@example.com',
                                image: 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80',
                                role: 'owner',
                                status: 'online'
                            }
                        ]
                    })
                ],
                code: `import AccountCard from "./examples/share-document-card.js";

ShareDocumentCard({
    link: 'http://example.com/link/to/document',
    people: [
        {
            name: 'Leslie Alexander',
            email: 'leslie.alexander@example.com',
            image: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80',
            role: 'owner',
            lastSeen: '2023-01-23T13:23Z',
            status: 'offline'
        },
        {
            name: 'Michael Foster',
            email: 'michael.foster@example.com',
            image: 'https://images.unsplash.com/photo-1519244703995-f4e0f30006d5?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80',
            role: 'member',
            lastSeen: '2023-01-23T13:23Z',
            status: 'offline'
        },
        {
            name: 'Dries Vincent',
            email: 'dries.vincent@example.com',
            image: 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80',
            role: 'owner',
            status: 'online'
        }
    ]
})`
            }),

            DocSection({
                title: 'Date Select Card',
                preview: [
                    DateSelectCard({
                        startDate: '2023-01-23'
                    })
                ],
                code: `import AccountCard from "./examples/date-select-card.js";

DateSelectCard({
    startDate: '2023-01-23'
})`
            }),

            DocSection({
                title: 'Report Issue Card',
                preview: [
                    ReportIssueCard()
                ],
                code: `import AccountCard from "./examples/report-issue-card.js";

ReportIssueCard()`
            }),

            DocSection({
                title: 'Project Card',
                preview: [
                    ProjectCard()
                ],
                code: `import AccountCard from "./examples/project-card.js";

ProjectCard()`
            }),

            DocSection({
                title: 'Cookie Settings Card',
                preview: [
                    CookieSettingsCard()
                ],
                code: `import AccountCard from "./examples/cookie-settings-card.js";

CookieSettingsCard()`
            })
        ]
    )
);

export default CardPage;