import { H4, Li, Section, Ul } from '@base-framework/atoms';
import { DocPage } from '../../../../doc-page.js';

/**
 * CTOPage
 *
 * This page outlines the expectations, responsibilities and goals for the Chief Technology Officer (CTO) position.
 *
 * @returns {DocPage}
 */
export const CTOPage = () => (
    DocPage(
        {
            title: 'Chief Technology Officer (CTO)',
            description: `The Chief Technology Officer (CTO) is a key leadership role in the organization, responsible for overseeing the strategic direction, development, and execution of our technology vision. This role requires strong technical expertise, business acumen, and the ability to lead, inspire, and collaborate effectively with cross-functional teams. This document outlines the expectations, responsibilities, and goals associated with the CTO position.`
        },
        [
            // Responsibilities Section
            Section({ class: 'space-y-4 mt-12' }, [
                H4({ class: 'text-2xl font-bold' }, 'Responsibilities'),
                Ul({ class: 'list-disc pl-6 space-y-1 text-muted-foreground' }, [
                    Li("Define and communicate the technology vision and strategy, aligning it with the company's overall mission, goals, and objectives."),
                    Li("Oversee the development, implementation, and maintenance of all software solutions, ensuring their quality, reliability, and performance."),
                    Li("Lead and manage the Technical Department, fostering a culture of collaboration, innovation, and continuous improvement."),
                    Li("Collaborate with executive leadership and other key stakeholders to identify, evaluate, and prioritize technology initiatives, ensuring that they align with business requirements and deliver maximum value."),
                    Li("Develop and maintain strong relationships with industry partners, vendors, and other external stakeholders, staying abreast of emerging trends and technologies that could impact the organization."),
                    Li("Ensure the organization's technology infrastructure, systems, and processes are scalable, secure, and compliant with applicable laws and regulations."),
                    Li("Serve as a technology evangelist, promoting the adoption of new technologies, best practices, and methodologies within the organization.")
                ])
            ]),
            // Goals Section
            Section({ class: 'space-y-4 mt-12' }, [
                H4({ class: 'text-2xl font-bold' }, 'Goals'),
                Ul({ class: 'list-disc pl-6 space-y-1 text-muted-foreground' }, [
                    Li("Drive the successful execution of the technology vision and strategy, ensuring that it remains aligned with the company's overall mission, goals, and objectives."),
                    Li("Continuously improve the quality, reliability, and performance of our software solutions, ensuring that they meet or exceed customer expectations."),
                    Li("Develop and maintain a high-performing Technical Department, fostering an environment of collaboration, innovation, and continuous learning."),
                    Li("Ensure the successful delivery of technology initiatives, balancing the need for innovation with the importance of delivering value to the business."),
                    Li("Promote a culture of security and compliance, ensuring that the organization's technology infrastructure, systems, and processes remain resilient and protected.")
                ])
            ])
        ]
    )
);

export default CTOPage;
