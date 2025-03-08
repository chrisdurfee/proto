import { Div } from "@base-framework/atoms";
import { Button, Fieldset, Input, Select, Textarea } from "@base-framework/ui/atoms";
import { Icons } from "@base-framework/ui/icons";
import { Confirmation, DatePicker, DetailBody, DetailSection, DropdownMenu, FormField, Modal, SplitRow } from "@base-framework/ui/molecules";
import { DocSection } from "../../../molecules/doc-section.js";
import { DocPage } from '../../doc-page.js';

/**
 * Basic modal size/type combos for demonstration.
 */
const Modals = [
    { label: 'Large',  buttonStyle: 'primary',    size: 'lg'   },
    { label: 'Small',  buttonStyle: 'secondary',  size: 'sm'   },
    { label: 'Medium', buttonStyle: 'destructive',size: 'md'   },
    { label: 'XL',     buttonStyle: 'primary',    size: 'xl'   },
    { label: 'Right',  buttonStyle: 'ghost',      type: 'right'},
    { label: 'Left',   buttonStyle: 'link',       type: 'left' }
];

/**
 * Creates a simple modal button that opens a demo modal with given size/type.
 *
 * @param {object} props
 * @returns {object}
 */
const ModalButton = ({ label, buttonStyle, size, type }) => Button({
    text: label,
    class: `m-1 ${buttonStyle}`,
    click: () => new Modal({
        title: 'Are you absolutely sure?',
        size,
        type
    },
    [
        Div({ class: 'flex flex-col max-w-lg lg:p-4 space-y-8' }, [
            new FormField({
                name: "popover-test",
                label: "Popover Test",
            }, [ new DatePicker() ]),
            new FormField({
                name: "dialog-test",
                label: "Dialog Test",
            }, [
                Button({
                    click: () =>
                    {
                        new Confirmation({
                            title: 'Are you absolutely sure?',
                            description: 'This action cannot be undone.',
                            confirmTextLabel: 'Save',
                            confirmed: () =>
                            {
                                // @ts-ignore
                                app.notify({
                                    icon: Icons.trash,
                                    title: 'Action complete',
                                    description: 'You confirmed your selection.',
                                    type: 'destructive'
                                });
                            }
                        }).open();
                    }
                }, 'Confirm')
            ])
        ])
    ]).open()
});

/**
 * Demonstrates a form-based modal with fields.
 *
 * @param {object} props
 *   @prop {string} size
 *   @prop {string} type
 * @returns {Modal}
 */
const ModalForm = ({ size = 'md', type = 'center' }) =>
    new Modal({
        title: 'Report an Issue',
        icon: Icons.warning,
        description: "What area are you having problems with?",
        size,
        type,
        onSubmit: () => console.log('Form submitted')
    },
    [
        Div({ class: 'flex flex-col lg:p-4 space-y-8' }, [
            Fieldset({ legend: 'Issue Details' }, [
                Div({ class: "flex flex-auto w-full gap-4" }, [
                    new FormField({ name: "area", label: "Area" }, [
                        Select({
                            required: true,
                            class: "border p-2 rounded-md w-full bg-background text-foreground",
                            options: [
                                { value: "billing", label: "Billing" },
                                { value: "technical", label: "Technical" },
                                { value: "account", label: "Account" },
                                { value: "other", label: "Other" }
                            ],
                            defaultValue: "billing"
                        })
                    ]),
                    new FormField({ name: "security_level", label: "Security Level" }, [
                        Select({
                            required: true,
                            class: "border p-2 rounded-md w-full bg-background text-foreground",
                            options: [
                                { value: "severity_1", label: "Severity 1" },
                                { value: "severity_2", label: "Severity 2" },
                                { value: "severity_3", label: "Severity 3" }
                            ],
                            defaultValue: "severity_2"
                        })
                    ])
                ]),
                new FormField({ name: "subject", label: "Subject" }, [
                    Input({
                        type: "text",
                        placeholder: "I need help with...",
                        required: true,
                        class: "border p-2 rounded-md w-full bg-background text-foreground placeholder-muted-foreground"
                    })
                ]),
                new FormField({ name: "description", label: "Description" }, [
                    Textarea({
                        placeholder: "Please include all information relevant to your issue.",
                        required: true,
                        class: "border p-2 rounded-md w-full bg-background text-foreground placeholder-muted-foreground"
                    })
                ])
            ])
        ])
    ]);

/**
 * A button to open the above form-based modal.
 *
 * @param {object} props
 * @returns {object}
 */
const ModalFormButton = ({ label, buttonStyle, size, type }) => Button({
    text: label,
    class: `m-1 ${buttonStyle}`,
    click: () => ModalForm({ size, type }).open()
});

/**
 * Demonstrates how to pass header options to a modal via `headerOptions`.
 */
const DemoHeaderOptions = () => [
    new DropdownMenu({
        icon: Icons.ellipsis.vertical,
        groups: [
            [
                { icon: Icons.mapPin, label: 'Track Item', value: 'track-item' },
                { icon: Icons.trash,  label: 'Delete Item', value: 'delete-item' }
            ]
        ],
        onSelect: (selected) => {
            console.log("Selected item from header:", selected);
        }
    })
];

/**
 * Demonstrates how to use the "DetailBody", "DetailSection", and "SplitRow" atoms.
 */
const DemoDetailModal = () =>
    new Modal({
        title: 'Detail Example',
		description: 'This is a detailed view of an item.',
        icon: Icons.information,
        size: 'md',
        headerOptions: DemoHeaderOptions, // <<---- Demonstrates custom header actions
        hidePrimaryButton: true
    },
    [
        // We can layout the content using detail atoms
        DetailBody([
            DetailSection({ title: 'Overview' }, [
                SplitRow('Label', 'Value'),
                SplitRow('Date', '2024-11-02'),
                Div({ class: 'my-2 border-t' }),
                SplitRow('Status', 'Pending')
            ]),
            DetailSection({ title: 'More Info' }, [
                SplitRow('Description', 'This is a multi-line snippet.'),
                SplitRow('Comments', 'No comments available.')
            ])
        ])
    ]);

/**
 * A button to open the DemoDetailModal
 */
const DetailModalButton = () => Button({
    text: 'Detail Modal',
    variant: 'outline',
    click: () => DemoDetailModal().open()
});

/**
 * ModalPage
 *
 * Provides documentation and examples for modals,
 * including header options and detail modal atoms.
 *
 * @returns {DocPage}
 */
export const ModalPage = () =>
    DocPage(
    {
        title: 'Modals',
        description: 'Displays modals or modal-like components with various sizes, forms, and header options.'
    },
    [
        // A doc section showcasing "detail modal atoms" usage
        DocSection({
            title: 'Detail Modal Atoms',
            description: 'Demonstrates usage of DetailBody, DetailSection, and SplitRow within a modal.',
            preview: [
                DetailModalButton() // The button that opens the DemoDetailModal
            ],
            code: `
// detail-modal-example.js
import {  DetailBody, DetailSection, SplitRow  } from "@base-framework/ui/molecules";
import {  Modal  } from "@base-framework/ui/molecules";
import {  Icons  } from "@base-framework/ui/icons";

const DemoDetailModal = () =>
    new Modal({
        title: 'Detail Example',
        icon: Icons.information,
        size: 'md',
        hidePrimaryButton: true
    },
    [
        DetailBody([
            DetailSection({ title: 'Overview' }, [
                SplitRow('Label', 'Value'),
                SplitRow('Date', '2024-11-02'),
            ]),
            DetailSection({ title: 'More Info' }, [
                SplitRow('Description', 'This is a multi-line snippet.'),
            ])
        ])
    ]);
`
        }),

        // A doc section specifically describing "headerOptions"
        DocSection({
            title: 'Header Options',
            description: 'You can pass `headerOptions` as an array of atoms for the modal’s top-right actions. Below is an example with a DropdownMenu in the modal header.',
            preview: [
                Button({
                    text: 'Open Modal with Header Options',
                    variant: 'secondary',
                    click: () =>
                        new Modal({
                            title: 'Modal with Header Options',
                            icon: Icons.settings,
                            headerOptions: DemoHeaderOptions, // demonstration
                            hidePrimaryButton: true
                        },
                        [
                            Div({ class: 'p-4' }, 'This modal has an ellipsis menu in the header.')
                        ]).open()
                })
            ],
            code: `
// header-options-example.js
import {  Modal  } from "@base-framework/ui/molecules";
import {  DropdownMenu  } from "@base-framework/ui/molecules";
import {  Icons  } from "@base-framework/ui/icons";

// Example header options
const DemoHeaderOptions = () => [
    new DropdownMenu({
        icon: Icons.ellipsis.vertical,
        groups: [
            [
                { icon: Icons.mapPin, label: 'Track Item', value: 'track-item' },
                { icon: Icons.trash,  label: 'Delete Item', value: 'delete-item' }
            ]
        ],
        onSelect: (selected) => {
            console.log("Selected item from header:", selected);
        }
    })
];

new Modal({
    title: 'Modal with Header Options',
    icon: Icons.settings,
    headerOptions: DemoHeaderOptions,  // pass the array here
    hidePrimaryButton: true
},
[
    // modal content...
]).open();
`
        }),

        // Examples of different sized modals
        DocSection({
            title: 'Extra Large Modal',
            description: 'An extra large modal example.',
            preview: [
                ModalButton(Modals[3])
            ],
            code: `
import {  Modal  } from "@base-framework/ui/molecules";
import {  Button  } from "@base-framework/ui/atoms";

/**
 * This creates a button that opens an XL modal.
 */
const ModalButton = ({ label, size }) => Button({
    text: label,
    click: () => new Modal({
        size: 'xl',
        title: 'Are you absolutely sure?'
    }).open()
});`
        }),

        DocSection({
            title: 'Large Modal',
            preview: [
                ModalButton(Modals[0])
            ],
            code: `
import {  Modal  } from "@base-framework/ui/molecules";
import {  Button  } from "@base-framework/ui/atoms";

const ModalButton = ({ label, size }) => Button({
    text: label,
    click: () => new Modal({
        size: 'lg',
        title: 'Large Modal Example'
    }).open()
});`
        }),

        DocSection({
            title: 'Medium Modal',
            preview: [
                ModalButton(Modals[2])
            ],
            code: `
import {  Modal  } from "@base-framework/ui/molecules";
import {  Button  } from "@base-framework/ui/atoms";

const ModalButton = ({ label, size }) => Button({
    text: label,
    click: () => new Modal({
        size: 'md',
        title: 'Medium Modal Example'
    }).open()
});`
        }),

        DocSection({
            title: 'Right Modal',
            preview: [
                ModalButton(Modals[4])
            ],
            code: `
import {  Modal  } from "@base-framework/ui/molecules";
import {  Button  } from "@base-framework/ui/atoms";

const ModalButton = ({ label, type }) => Button({
    text: label,
    click: () => new Modal({
        type: 'right',
        title: 'Right Modal Example'
    }).open()
});`
        }),

        // Form-based modals
        DocSection({
            title: 'Form Modal',
            preview: [
                ModalFormButton({
                    label: 'Form Modal',
                    buttonStyle: 'primary'
                })
            ],
            code: `
import {  Modal  } from "@base-framework/ui/molecules";
import {  Button  } from "@base-framework/ui/atoms";
import {  Fieldset  } from "@base-framework/ui/atoms";
import { Div } from "@base-framework/atoms";
import {  Input, Textarea  } from "@base-framework/ui/atoms";
import {  FormField  } from "@base-framework/ui/molecules";

const ModalForm = ({ size = 'lg', type = 'center' }) => (
    new Modal({
        title: 'Report an Issue',
        size,
        type,
        onSubmit: () => console.log('Form submitted')
    }, [
        // ...
    ])
);
`
        }),

        DocSection({
            title: 'Right Form Modal',
            preview: [
                ModalFormButton({
                    label: 'Form Modal (Right)',
                    buttonStyle: 'primary',
                    size: 'md',
                    type: 'right'
                })
            ],
            code: `
import {  Modal  } from "@base-framework/ui/molecules";
import {  Button  } from "@base-framework/ui/atoms";

const ModalForm = ({ type = 'right', size = 'md' }) => (
    new Modal({
        title: 'Report an Issue',
        type,
        size,
        onSubmit: () => console.log('Form submitted')
    }, [
        // ...
    ])
);
`
        })
    ]
);

export default ModalPage;