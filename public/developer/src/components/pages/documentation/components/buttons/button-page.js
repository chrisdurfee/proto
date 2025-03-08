import { H2, Header } from "@base-framework/atoms";
import { Button, LoadingButton } from "@base-framework/ui/atoms";
import { Icons } from "@base-framework/ui/icons";
import { DocSection } from "../../../../molecules/doc-section.js";
import { DocPage } from '../../../doc-page.js';

/**
 * ButtonPage
 *
 * This will create a button page.
 *
 * @returns {DocPage}
 */
export const ButtonPage = () => (
	DocPage(
        {
            title: 'Button',
            description: 'Displays a button or a component that looks like a button.'
        },
        [
            DocSection({
                title: 'Usage',
                description: 'Import the button atom and use it in your components. This platform has variants for different use cases. The variant can be set using the `variant` prop.',
                preview: [
                    Button({ variant: 'primary' }, 'Click Me')
                ],
                code: `import {  Button  } from "@base-framework/ui/atoms";

Button({ variant: 'primary', click: () => console.log('clicked') }, 'Click Me')`
            }),

            Header({ class: 'flex flex-col py-8' }, [
                H2({ class: 'text-3xl font-bold tracking-tight border-b pb-2' }, 'Variants'),
            ]),

            DocSection({
                title: 'Primary Button',
                description: 'This is a primary button.',
                preview: [
                    Button({ variant: 'primary' }, 'Click Me')
                ],
                code: `
import {  Button  } from "@base-framework/ui/atoms";
import { Atom } from '@base-framework/base';

export const PrimaryButton = Atom((props, children) => (
    Button({ ...props, variant: 'primary' }, children)
);`
            }),

            DocSection({
                title: 'Secondary Button',
                preview: [
                    Button({ variant: 'secondary' }, 'Secondary')
                ],
                code: `
import {  Button  } from "@base-framework/ui/atoms";
import { Atom } from '@base-framework/base';

export const SecondaryButton = Atom((props, children) => (
    Button({ ...props, variant: 'secondary' }, children)
);`
            }),

            DocSection({
                title: 'Destructive Button',
                preview: [
                    Button({ variant: 'destructive' }, 'Destructive')
                ],
                code: `
import {  Button  } from "@base-framework/ui/atoms";
import { Atom } from '@base-framework/base';

export const DestructiveButton = Atom((props, children) => (
    Button({ ...props, variant: 'destructive' }, children)
);`
            }),

            DocSection({
                title: 'Warning Button',
                preview: [
                    Button({ variant: 'warning' }, 'Warning')
                ],
                code: `import {  Button  } from "@base-framework/ui/atoms";
import { Atom } from '@base-framework/base';

export const WarningButton = Atom((props, children) => (
    Button({ ...props, variant: 'warning' }, children)
);`
            }),

            DocSection({
                title: 'Outline Button',
                preview: [
                    Button({ variant: 'outline' }, 'Outline')
                ],
                code: `
import {  Button  } from "@base-framework/ui/atoms";
import { Atom } from '@base-framework/base';

export const OutlineButton = Atom((props, children) => (
    Button({ ...props, variant: 'outline' }, children)
);`
            }),

            DocSection({
                title: 'Ghost Button',
                preview: [
                    Button({ variant: 'ghost' }, 'Ghost')
                ],
                code: `
import {  Button  } from "@base-framework/ui/atoms";
import { Atom } from '@base-framework/base';

export const GhostButton = Atom((props, children) => (
    Button({ ...props, variant: 'ghost' }, children)
);`
            }),

            DocSection({
                title: 'Link Button',
                preview: [
                    Button({ variant: 'link' }, 'Link')
                ],
                code: `
import {  Button  } from "@base-framework/ui/atoms";
import { Atom } from '@base-framework/base';

export const LinkButton = Atom((props, children) => (
    Button({ ...props, variant: 'link' }, children)
);`
            }),

            DocSection({
                title: 'Icon Button',
                description: 'Displays a button with an icon.',
                preview: [
                    Button({ variant: 'icon', icon: Icons.home })
                ],
                code: `import {  Button  } from "@base-framework/ui/atoms";
import {  Icons  } from "@base-framework/ui/icons";
import { Atom } from '@base-framework/base';

export const IconButton = Atom((props) => (
    Button({ ...props, variant: 'icon', icon: Icons.home })
);`
            }),

            DocSection({
                title: 'With Icon Button',
                preview: [
                    Button({ variant: 'withIcon', icon: Icons.home }, 'With Icon')
                ],
                code: `import {  Button  } from "@base-framework/ui/atoms";
import {  Icons  } from "@base-framework/ui/icons";

Button({ variant: 'withIcon', icon: Icons.home }, 'With Icon')`
            }),

            DocSection({
                title: 'Loading Button',
                preview: [
                    LoadingButton('With Icon')
                ],
                code: `import {  LoadingButton  } from "@base-framework/ui/atoms";

LoadingButton('With Icon')`
            })
        ]
    )
);

export default ButtonPage;