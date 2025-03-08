import { Div, H3, H4, H5, P, Section } from "@base-framework/atoms";
import { Icons } from "@base-framework/ui/icons";
import { ThemeToggle } from "@base-framework/ui/molecules";
import { DocPage } from "../../doc-page.js";

/**
 * This will create an color card.
 *
 * @param {object} color
 * @returns {object}
 */
const ColorCard = (color) =>
{
    return Div({ class: 'flex flex-auto flex-col items-center gap-2 max-w-[150px] h-[8.5rem] cursor-pointer' }, [
        Div({ class: `flex flex-auto w-full h-full min-h-[136px] justify-center items-center flex-wrap rounded-lg border text-base shadow-sm ${color.class}`,
            click: () =>
            {
                // copy to clipboard
                navigator.clipboard.writeText(`${color.class}`);

                // @ts-ignore
                app.notify({
                    title: "Color copied",
                    description: `The color '${color.class}' has been copied to your clipboard.`,
                    icon: Icons.clipboard.checked
                });
            }
        }),
        Div({ class: 'text-sm text-muted-foreground' }, color.var)
    ]);
};

/**
 * This will create an color row.
 *
 * @param {Array<object>} colors
 *
 */
const ColorRow = (title, colors) =>
{
    return Section({ class: 'flex flex-auto flex-col gap-2' }, [
        P({
            class: 'text-base text-muted-foreground'
        }, title),
        Div({ class: 'flex flex-auto flex-wrap gap-2 my-2', map: [colors, ColorCard] })
    ]);
};

/**
 * This will greate the color guide layout.
 *
 * @param {object} obj
 * @returns {array}
 */
const ColorGuide = (obj) =>
{
    return Div({ class: 'flex flex-auto flex-col gap-12' }, [

        H5({
            class: 'text-2xl font-bold'
        }, 'Theme Toggle'),

        new ThemeToggle(),

        H5({
            class: 'text-2xl font-bold'
        }, 'Color Guide'),

        ColorRow('Default background color of <body />...etc', [
            { var: '--backround', class: 'bg-backround' },
            { var: '--foreground', class: 'bg-foreground' },
        ]),

        ColorRow('Muted backgrounds such as <TabsList />, <Skeleton /> and <Switch />', [
            { var: '--muted', class: 'bg-muted' },
            { var: '--muted-foreground', class: 'bg-muted-forground' },
        ]),

        ColorRow('Background color for <Card />', [
            { var: '--card', class: 'bg-card' },
            { var: '--card-foreground', class: 'bg-card-foreground' },
        ]),

        ColorRow('Background color for popovers such as <DropdownMenu />, <HoverCard />, <Popover />', [
            { var: '--popover', class: 'bg-popover' },
            { var: '--popover-foreground', class: 'bg-popover-foreground' },
        ]),

        ColorRow('Default border color', [
            { var: '--border', class: 'bg-border' },
        ]),

        ColorRow('Border color for inputs such as <Input />, <Select />, <Textarea />', [
            { var: '--input', class: 'bg-input' },
        ]),

        ColorRow('Primary colors for <Button />', [
            { var: '--primary', class: 'bg-primary' },
            { var: '--primary-foreground', class: 'bg-primary-foreground' },
        ]),

        ColorRow('Secondary colors for <Button />', [
            { var: '--secondary', class: 'bg-secondary' },
            { var: '--secondary-foreground', class: 'bg-secondary-foreground' },
        ]),

        ColorRow('Used for accents such as hover effects on <DropdownMenuItem>, <SelectItem>...etc', [
            { var: '--accent', class: 'bg-accent' },
            { var: '--accent-foreground', class: 'bg-accent-foreground' },
        ]),

        ColorRow('Used for destructive actions such as <Button variant="destructive">', [
            { var: '--destructive', class: 'bg-destructive' },
            { var: '--destructive-foreground', class: 'bg-destructive-foreground' },
        ]),

        ColorRow('Used for warning actions such as <Button variant="warning">', [
            { var: '--warning', class: 'bg-warning' },
            { var: '--warning-foreground', class: 'bg-warning-foreground' },
        ]),

        ColorRow('Used for focus ring', [
            { var: '--ring', class: 'bg-ring' },
        ]),

        ColorRow('Border radius for card, input and buttons', [
            { var: '--radius', class: 'bg-radius' },
        ]),
    ]);
};

/**
 * ThemePage
 *
 * This will create an theme page.
 *
 * @returns {DocPage}
 */
export const ThemePage = () => (
    DocPage(
        {
            title: 'Theme',
            description: 'The theme is using Tailwind CSS and is based on Shadcn UI. The theme is customizable and can be extended. It comes with light and dark theme support.',
        },
        [
            P({ class: 'text-muted-foreground' }, { html: 'To learn about Shandcn UI, visit the official documentation: <a href="https://ui.shadcn.com/docs/theming" class="underline" target="_blank">Shadcn Documentation' }),

            Div({ class: 'my-20' }, [
                H3({ class: 'text-2xl font-bold' }, 'Default Styles'),
                P({ class: 'text-muted-foreground' }, 'The default styles are defined in the Tailwind CSS configuration file and in the base CSS file. Here\'s the list of variables available for customization:'),
            ]),
            ColorGuide(),

            Div({ class: 'flex flex-auto flex-col gap-2 mt-20' }, [
                Div({ class: 'flex flex-auto flex-col gap-2' }, [
                    H4({ class: 'text-2xl font-bold' }, 'Text Sizes'),

                    P({ class: 'text-muted-foreground text-3xl' }, 'Text-3xl A test sentence.'),

                    P({ class: 'text-muted-foreground text-2xl' }, 'Text-2xl A test sentence.'),

                    P({ class: 'text-muted-foreground text-xl' }, 'Text-xl A test sentence.'),

                    P({ class: 'text-muted-foreground text-lg' }, 'Text-lg A test sentence.'),

                    P({ class: 'text-muted-foreground text-base' }, 'Text-base A test sentence.'),

                    P({ class: 'text-muted-foreground text-sm' }, 'Text-sm A test sentence.'),

                    P({ class: 'text-muted-foreground text-xs' }, 'Text-xs A test sentence.'),
                ]),
            ])
        ]
    )
);

export default ThemePage;