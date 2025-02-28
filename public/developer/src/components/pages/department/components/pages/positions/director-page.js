import { H4, Li, Section, Ul } from '@base-framework/atoms';
import { DocPage } from '../../../../doc-page.js';

/**
 * Department Director Page
 *
 * This page outlines the expectations, responsibilities and goals for the Department Director position.
 *
 * @returns {DocPage}
 */
export const DirectorPage = () => (
    DocPage(
        {
            title: 'Department Director',
            description: `The director of Product Development oversees the development of our products and services. These areas include planning, research, design, development, testing, and maintenance.

The director acts as lead architect on our platforms and develops many tools, frameworks, patterns, and infrastructure to reduce development time and decrease the friction to allow our team members to have optimal working environments.

The director researches new technologies, platforms, tools, and programming languages to enable our projects to scale and pivot.

The director also mentors and trains the design/developers to multiply the talent, and develop new features and services with the group. To keep our products competitive, this position performs market comparisons and searches for areas to reduce product friction and develop disruptive services.`
        },
        [
            // Responsibilities Section
            Section({ class: 'space-y-4 mt-12' }, [
                H4({ class: 'text-2xl font-bold' }, 'Responsibilities'),
                Ul({ class: 'list-disc pl-6 space-y-1 text-muted-foreground' }, [
                    Li('Research future platforms, tools, technologies, and programming languages.'),
                    Li('Develop new technologies to increase the speed and performance of team members and projects.'),
                    Li('Work with our Project Manager to break down the current tasks and priorities to optimize the department bandwidth.'),
                    Li('Work with clients and team members on user feedback to iterate the products to decrease friction.'),
                    Li('Train and mentor team members and implement ongoing changes to keep pushing innovation.'),
                    Li('Build new features and services.'),
                    Li('Perform market comparisons to stay competitive.')
                ])
            ]),
            // Goals Section
            Section({ class: 'space-y-4 mt-12' }, [
                H4({ class: 'text-2xl font-bold' }, 'Goals'),
                Ul({ class: 'list-disc pl-6 space-y-1 text-muted-foreground' }, [
                    Li('Drive innovation and streamline product development cycles, ensuring scalability and reduced friction.'),
                    Li('Mentor and empower design/developers to achieve operational excellence and foster continuous improvement.')
                ])
            ])
        ]
    )
);

export default DirectorPage;
