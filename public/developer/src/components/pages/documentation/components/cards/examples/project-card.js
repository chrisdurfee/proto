import { Div, Span } from "@base-framework/atoms";
import { Button, Card, Icon } from "@base-framework/ui/atoms";
import { Icons } from "@base-framework/ui/icons";

/**
 * ProjectRow Component
 *
 * Displays individual project details, including name, description, and metadata.
 *
 * @param {object} project - Information about the project.
 * @returns {object} - A row for each project.
 */
const ProjectRow = ({ name, description, stars, language, lastUpdated }) => (
    Div({ class: "flex items-start justify-between py-4 border-b border-muted last:border-b-0" }, [
        // Project Information
        Div({ class: "flex flex-col" }, [
            // Project Name
            Span({ class: "font-semibold text-foreground" }, name),
            // Project Description
            Span({ class: "text-sm text-muted-foreground" }, description),

            // Project Metadata
            Div({ class: "flex space-x-4 text-sm text-muted-foreground mt-3" }, [
                Div({ class: 'flex items-center gap-2' }, [
                    Icon({ class: " text-blue-500" }, Icons.document.text),
                    Span(` ${language}`)
                ]),
                Div({ class: 'flex items-center' }, [
                    Icon(Icons.star),
                    Span(` ${stars}`)
                ]),
                Div(`Updated ${lastUpdated}`)
            ])
        ]),

        // Star Button
        Button({
            variant: 'secondary',
            class: "px-3 py-1 gap-1 flex items-center rounded-md",
            click: () => console.log(`Starred ${name}`)
        }, [
            Icon(Icons.star),
            Span("Star")
        ])
    ])
);

/**
 * ProjectList Component
 *
 * Renders a list of project rows.
 *
 * @param {object} props
 * @returns {object} - List of projects.
 */
const ProjectList = ({ projects }) => (
    Div({ class: "space-y-3" }, projects.map(project => ProjectRow(project)))
);

const projects = [
    {
        name: "shadcn/ui",
        description: "Beautifully designed components that you can copy and paste into your apps. Accessible. Customizable. Open Source.",
        stars: "20k",
        language: "TypeScript",
        lastUpdated: "April 2023"
    },
    {
        name: "base-framework",
        description: "A robust framework for building and structuring large-scale applications with ease.",
        stars: "2",
        language: "JavaScript",
        lastUpdated: "June 2024"
    }
];

/**
 * ProjectCard Component
 *
 * Displays a card with a header and a list of project rows.
 *
 * @returns {object} - The complete project card.
 */
export const ProjectCard = () => {
    return Card({ class: "w-full max-w-md mx-auto p-6 bg-card space-y-6" }, [
        Div({ class: "font-semibold text-lg text-foreground" }, "Projects"),
        ProjectList({ projects })
    ]);
};

export default ProjectCard;
