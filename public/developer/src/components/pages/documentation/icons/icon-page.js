import { A, Div, H3, H5, I, P, Span } from "@base-framework/atoms";
import { Strings } from "@base-framework/base";
import { Icon } from "@base-framework/ui/atoms";
import { Icons } from "@base-framework/ui/icons";
import { DocSection } from "../../../molecules/doc-section.js";
import { DocPage } from "../../doc-page.js";

/**
 * This will create an icon card.
 *
 * @param {string} icon
 * @param {string} iconName
 * @param {string|null} path
 * @returns {object}
 */
const IconCard = (icon, iconName, path = null) =>
{
    path = 'Icons' + ((path) ? '.' + path : '');
    return Div({ class: 'flex flex-auto flex-col items-center gap-2 w-full max-w-[150px] h-[8.5rem] cursor-pointer' }, [
        Div({ class: 'flex flex-auto w-full h-full justify-center items-center flex-wrap rounded-lg border bg-card text-base shadow-sm hover:bg-muted/50',
            click: () =>
            {
                // copy to clipboard
                navigator.clipboard.writeText(`${path}.${iconName}`);

                // @ts-ignore
                app.notify({
                    title: "Icon copied",
                    description: `The icon '${path}.${iconName}' has been copied to your clipboard.`,
                    icon: Icons.clipboard.checked
                });
            }
        }, [
            Icon(icon),
        ]),
        Div({ class: 'text-muted-foreground' }, iconName)
    ]);
};

/**
 * This will create an icon row.
 *
 * @param {*} icons
 * @param {array} layout
 * @param {string|null} path
 */
const IconRow = (icons, layout, path) =>
{
    const isIconObject = (typeof icons === 'object');
    if (isIconObject)
    {
        layout.push(P({ class: 'text-muted-foreground' }, 'path: ' + path + '.{icon}'));
    }

    const row = [];
    layout.push(Div({ class: 'flex flex-auto flex-wrap gap-2 my-8' }, row));

    if (isIconObject === false)
    {
        row.push(IconCard(icons, path));
    }
    else
    {
        for (var prop in icons)
        {
            if (icons.hasOwnProperty(prop) === false)
            {
                continue;
            }

            const icon = icons[prop];
            if(typeof icon !== 'object')
            {
                row.push(IconCard(icon, prop, path));
                continue;
            }

            const childPath = `${path}.${prop}`;
            IconRow(icon, layout, childPath);
        }
    }
};

/**
 * This will greate the icon guide layout.
 *
 * @param {object} obj
 * @returns {array}
 */
const IconGuide = (obj) =>
{
    if (!obj || typeof obj !== 'object')
    {
        return;
    }

    const layout = [];
    for (let [prop, value] of Object.entries(obj))
    {
        if (prop === 'parse')
        {
            continue;
        }

        layout.push(H5({
            id: prop.toLowerCase(),
            class: 'text-2xl font-bold'
        }, Strings.titleCase(prop)));

        const icon = value;
        IconRow(icon, layout, prop);
    }

    return layout;
};

/**
 * IconPage
 *
 * This will create an icon page..
 *
 * @returns {DocPage}
 */
export const IconPage = () => (
    DocPage(
        {
            title: 'Icons',
            description: 'The icons are from the Hericon library. They are SVG icons that can be used in your project. This icon set uses the outline style.'
        },
        [
            A({ href: 'https://heroicons.com/', target: '_blank', class: 'bttn link' }, 'link: Hericons'),

            DocSection({
                title: 'Icons',
                description: 'This is an icon.',
                preview: [
                    I({ html: Icons.home} )
                ],
                code: `
import {  Icons  } from "@base-framework/ui/icons";
import { I } from '@base-framework/atoms';

export const HomeIcon = () => (
    I({ html: Icons.home} )
);`
            }),

            DocSection({
                title: 'Icon Atom',
                description: 'An Icon is a helper atom that makes it easier to use icons in your project. ',
                preview: [
                    Icon(Icons.home)
                ],
                code: `
import {  Icon  } from "@base-framework/ui/atoms";

Icon(Icons.home);`
            }),

            DocSection({
                title: 'Icon Sizes',
                description: 'Icons can be rendered in various sizes using the `class` property to apply Tailwind CSS classes. The following sizes are available: `xs`, `sm`, `md`, `lg`, `xl`, `2xl`, and `3xl`.',
                preview: [
                    Div({ class: 'flex flex-wrap gap-4 items-center' }, [
                        Div({ class: 'flex flex-col items-center justify-center' }, [
                            Icon({ size: 'xs' }, Icons.home),
                            Span({ class: 'text-sm text-muted-foreground' }, 'xs'),
                        ]),
                        Div({ class: 'flex flex-col items-center justify-center' }, [
                            Icon({ size: 'sm' }, Icons.home),
                            Span({ class: 'text-sm text-muted-foreground' }, 'sm'),
                        ]),
                        Div({ class: 'flex flex-col items-center justify-center' }, [
                            Icon({ size: 'md' }, Icons.home),
                            Span({ class: 'text-sm text-muted-foreground' }, 'md'),
                        ]),
                        Div({ class: 'flex flex-col items-center justify-center' }, [
                            Icon({ size: 'lg' }, Icons.home),
                            Span({ class: 'text-sm text-muted-foreground' }, 'lg'),
                        ]),
                        Div({ class: 'flex flex-col items-center justify-center' }, [
                            Icon({ size: 'xl' }, Icons.home),
                            Span({ class: 'text-sm text-muted-foreground' }, 'xl'),
                        ]),
                        Div({ class: 'flex flex-col items-center justify-center' }, [
                            Icon({ size: '2xl' }, Icons.home),
                            Span({ class: 'text-sm text-muted-foreground' }, '2xl'),
                        ]),
                        Div({ class: 'flex flex-col items-center justify-center' }, [
                            Icon({ size: '3xl' }, Icons.home),
                            Span({ class: 'text-sm text-muted-foreground' }, '3xl'),
                        ]),
                    ])

                ],
                code: `
import {  Icon  } from "@base-framework/ui/atoms";

Icon({ size: 'xs' }, Icons.home),
Span('Extra Small (4x4)'),
Icon({ size: 'sm' }, Icons.home),
Span('Small (6x6)'),
Icon({ size: 'md' }, Icons.home),
Span('Medium (8x8)'),
Icon({ size: 'lg' }, Icons.home),
Span('Large (10x10)'),
Icon({ size: 'xl' }, Icons.home),
Span('Extra Large (12x12)'),
Icon({ size: '2xl' }, Icons.home),
Span('2X Large (14x14)'),
Icon({ size: '3xl' }, Icons.home),
Span('3X Large (16x16)')`
            }),

            Div({ class: 'my-20' }, [
                H3({ class: 'text-2xl font-bold' }, 'Default Icons'),
                P({ class: 'text-muted-foreground' }, 'A few of the icons have been saved to the "Icons" object. You can use these icons in your project. Click to copy the Icon path. Here is a list of the icons that are available:'),
            ]),

            IconGuide(Icons)
        ]
    )
);

export default IconPage;