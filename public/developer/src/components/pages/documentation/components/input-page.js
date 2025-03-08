import { Div, H4, P } from "@base-framework/atoms";
import { Atom } from "@base-framework/base";
import { Button, Checkbox, ColorInput, DateInput, DateTimeInput, EmailInput, FileInput, HiddenInput, Input, MonthInput, NumberInput, PasswordInput, Radio, RangeSlider, Select, TelInput, Textarea, TimeInput, UrlInput, WeekInput } from "@base-framework/ui/atoms";
import { Counter, DatePicker, TimePicker, Toggle } from "@base-framework/ui/molecules";
import { SearchDropdown, SignaturePanel } from "@base-framework/ui/organisms";
import { DocSection } from "../../../molecules/doc-section.js";
import { DocPage } from '../../doc-page.js';

const frameworks = [
    { value: 'next.js', label: 'Next.js' },
    { value: 'sveltekit', label: 'SvelteKit' },
    { value: 'nuxt.js', label: 'Nuxt.js' },
    { value: 'remix', label: 'Remix' },
    { value: 'astro', label: 'Astro' },
];

/**
 * This will create an input preview.
 *
 * @param {object} props
 * @param {object} children
 * @returns {object}
 */
const InputPreview = Atom((props, children) => (
    Div({ ...props, class: 'flex flex-auto items-center justify-center w-full max-w-[350px]' }, children)
));

/**
 * NotificationToggle
 *
 * A component for rendering a toggle switch with a label and description.
 *
 * @param {object} props
 * @returns {object}
 */
const NotificationToggle = ({ label, description, active, onChange }) => (
    Div({ class: 'flex items-center justify-between p-4 border rounded-md bg-muted/10 border-muted-foreground/20 mb-4' }, [
        Div({ class: 'flex flex-col pr-8' }, [
            H4({ class: 'font-semibold' }, label),
            P({ class: 'text-sm text-muted-foreground' }, description)
        ]),
        new Toggle({
            active,
            change: onChange
        })
    ])
);

const options = [
    { label: 'Apple', value: 'apple' },
    { label: 'Banana', value: 'banana' },
    { label: 'Cherry', value: 'cherry' },
    { label: 'Date', value: 'date' },
    { label: 'Elderberry', value: 'elderberry' },
    { label: 'Fig', value: 'fig' },
    { label: 'Grape', value: 'grape' },
    { label: 'Honeydew', value: 'honeydew' }
];

/**
 * InputPage
 *
 * This will create a input page.
 *
 * @returns {DocPage}
 */
export const InputPage = () => (
	DocPage(
        {
            title: 'Inputs',
            description: 'Inputs are essential for gathering user data in forms.',
        },
        [
            DocSection({
                title: 'Usage',
                description: 'Import the input atom and use it in your components. The input can be of various types: text, password, email, etc.',
                preview: [
                    Div({ class: 'grid gap-2 w-full max-w-[350px]' }, [
                        Input({
                            type: 'text',
                            placeholder: 'Enter your text here...',
                            change: (e) => console.log(e.target.value)
                        }),
                        EmailInput({}),
                        TelInput({}),
                        Textarea({
                            placeholder: 'Enter your text here...'
                        })
                    ])
                ],
                code: `import {  Input, EmailInput, TelInput, Textarea  } from "@base-framework/ui/atoms";

Input({
    type: 'text',
    placeholder: 'Enter your text here...',
    change: (e) => console.log(e.target.value)
}),
EmailInput({}),
TelInput({}),
Textarea({
    placeholder: 'Enter your text here...'
})`
            }),

            // Search Dropdown
            DocSection({
                title: 'Search Dropdown',
                description: 'This is a search dropdown.',
                preview: [
                    Div({ class: 'relative w-full max-w-md' }, [
                        new SearchDropdown({ options, onSelect: (item) => console.log(item) })
                    ])
                ],
                code: `import {  SearchDropdown  } from "@base-framework/ui/organisms";

const options = [
    { label: 'Apple', value: 'apple' },
    { label: 'Banana', value: 'banana' },
    { label: 'Cherry', value: 'cherry' },
    { label: 'Date', value: 'date' },
    { label: 'Elderberry', value: 'elderberry' },
    { label: 'Fig', value: 'fig' },
    { label: 'Grape', value: 'grape' },
    { label: 'Honeydew', value: 'honeydew' }
];

new SearchDropdown({ options, onSelect: (item) => console.log(item) })
`
            }),

            DocSection({
                title: 'File Input',
                description: 'This is a file input.',
                preview: [
                    InputPreview([
                        FileInput({
                            multiple: true,
                            change: (e) => console.log(e.target.files)
                        })
                    ])
                ],
                code: `import {  EmailInput, FileInput, Input, TelInput, Textarea  } from "@base-framework/ui/atoms";

FileInput({
    multiple: true,
    change: (e) => console.log(e.target.files)
})`
            }),

            DocSection({
                title: 'Radio Input',
                description: 'This is a radio input.',
                preview: [
                    Div({ class: 'grid gap-2' }, [
                        Radio({
                            name: 'theme',
                            label: 'System',
                            value: 'system',
                        }),
                        Radio({
                            name: 'theme',
                            label: 'Light',
                            value: 'light',
                        }),
                        Radio({
                            name: 'theme',
                            label: 'Dark',
                            value: 'dark',
                        })
                    ])
                ],
                code: `import {  Radio  } from "@base-framework/ui/atoms";
import { Div } from "@base-framework/atoms";

Div({ class: 'grid gap-2' }, [
    Radio({
        name: 'theme',
        label: 'System',
        value: 'system',
    }),
    Radio({
        name: 'theme',
        label: 'Light',
        value: 'light',
    }),
    Radio({
        name: 'theme',
        label: 'Dark',
        value: 'dark',
    })
])`
            }),

            DocSection({
                title: 'Text Input',
                description: 'This is a text input.',
                preview: [
                    InputPreview([
                        Input({
                            type: 'text',
                            placeholder: 'Enter your text here...'
                        })
                    ])
                ],
                code: `
import {  EmailInput, FileInput, Input, TelInput, Textarea  } from "@base-framework/ui/atoms";

Input({
    type: 'text',
    placeholder: 'Enter your text here...'
})`
            }),

            DocSection({
                title: 'Tel Input',
                description: 'This is a phone input.',
                preview: [
                    InputPreview([
                        TelInput({
                        })
                    ])
                ],
                code: `
import {  EmailInput, FileInput, Input, TelInput, Textarea  } from "@base-framework/ui/atoms";

TelInput({
})`
            }),

            DocSection({
                title: 'Email Input',
                description: 'This is a email input.',
                preview: [
                    InputPreview([
                        EmailInput({

                        })
                    ])
                ],
                code: `
import {  EmailInput, FileInput, Input, TelInput, Textarea  } from "@base-framework/ui/atoms";

EmailInput({

})`
            }),

            // URL Input
            DocSection({
                title: "URL Input",
                description: "This is a URL input field.",
                preview: [
                    InputPreview([
                        UrlInput({
                            placeholder: "Enter a valid URL...",
                            change: (e) => console.log(e.target.value),
                        }),
                    ]),
                ],
                code: `import {  UrlInput  } from "@base-framework/ui/atoms";

UrlInput({
    placeholder: 'Enter a valid URL...',
    change: (e) => console.log(e.target.value),
});`,
            }),

            // Color Input
            DocSection({
                title: "Color Input",
                description: "This is a color input field.",
                preview: [
                    InputPreview([
                        ColorInput({
                            change: (e) => console.log(e.target.value),
                        }),
                    ]),
                ],
                code: `import {  ColorInput  } from "@base-framework/ui/atoms";

ColorInput({
    change: (e) => console.log(e.target.value),
});`,
            }),

            // Date Input
            DocSection({
                title: "Date Input",
                description: "This is a date input field.",
                preview: [
                    InputPreview([
                        DateInput({
                            change: (e) => console.log(e.target.value),
                        }),
                    ]),
                ],
                code: `import {  DateInput  } from "@base-framework/ui/atoms";

DateInput({
    change: (e) => console.log(e.target.value),
});`,
            }),

            // Time Input
            DocSection({
                title: "Time Input",
                description: "This is a time input field.",
                preview: [
                    InputPreview([
                        TimeInput({
                            change: (e) => console.log(e.target.value),
                        }),
                    ]),
                ],
                code: `import {  TimeInput  } from "@base-framework/ui/atoms";

TimeInput({
    change: (e) => console.log(e.target.value),
});`,
            }),

            // DateTime Input
            DocSection({
                title: "DateTime Input",
                description: "This is a datetime input field.",
                preview: [
                    InputPreview([
                        DateTimeInput({
                            change: (e) => console.log(e.target.value),
                        }),
                    ]),
                ],
                code: `import {  DateTimeInput  } from "@base-framework/ui/atoms";

DateTimeInput({
    change: (e) => console.log(e.target.value),
});`,
            }),

            // Week Input
            DocSection({
                title: "Week Input",
                description: "This is a week input field.",
                preview: [
                    InputPreview([
                        WeekInput({
                            change: (e) => console.log(e.target.value),
                        }),
                    ]),
                ],
                code: `import {  WeekInput  } from "@base-framework/ui/atoms";

WeekInput({
    change: (e) => console.log(e.target.value),
});`,
            }),

            // Month Input
            DocSection({
                title: "Month Input",
                description: "This is a month input field.",
                preview: [
                    InputPreview([
                        MonthInput({
                            change: (e) => console.log(e.target.value),
                        }),
                    ]),
                ],
                code: `import {  MonthInput  } from "@base-framework/ui/atoms";

MonthInput({
    change: (e) => console.log(e.target.value),
});`,
            }),

            DocSection({
                title: "Password Input",
                description: "This is a password input field.",
                preview: [
                    InputPreview([
                        PasswordInput({
                            class: 'w-12 h-12'
                        }),
                    ]),
                ],
                code: `import {  PasswordInput  } from "@base-framework/ui/atoms";

PasswordInput({
    class: 'w-12 h-12'
});`,
            }),

            // Number Input
            DocSection({
                title: "Number Input",
                description: "This is a number input field.",
                preview: [
                    InputPreview([
                        NumberInput({
                            placeholder: "Enter a number...",
                            change: (e) => console.log(e.target.value),
                        }),
                    ]),
                ],
                code: `import {  NumberInput  } from "@base-framework/ui/atoms";

NumberInput({
    placeholder: 'Enter a number...',
    change: (e) => console.log(e.target.value),
});`,
            }),

            // Hidden Input
            DocSection({
                title: "Hidden Input",
                description: "This is a hidden input field.",
                preview: [
                    InputPreview([
                        HiddenInput({
                            value: "hidden-value",
                        }),
                    ]),
                ],
                code: `import {  HiddenInput  } from "@base-framework/ui/atoms";

HiddenInput({
    value: 'hidden-value',
});`,
            }),

            DocSection({
                title: 'Checkbox',
                description: 'This is a checkbox input.',
                preview: [
                    new Checkbox({
                        label: 'Check me',
                        onChange: (checked) => console.log(checked)
                    })
                ],
                code: `
import {  Checkbox  } from "@base-framework/ui/atoms";

new Checkbox({
    label: 'Check me',
    onChange: (checked) => console.log(checked)
})`
            }),

            DocSection({
                title: 'Select',
                description: 'This is a select input.',
                preview: [
                    InputPreview([
                        Select({
                            options: frameworks,
                            change: (e) => console.log(e.target.value)
                        })
                    ])
                ],
                code: `import {  Select  } from "@base-framework/ui/atoms";

Select({
    options: [
        { value: 'next.js', label: 'Next.js' },
        { value: 'sveltekit', label: 'SvelteKit' },
        { value: 'nuxt.js', label: 'Nuxt.js' },
        { value: 'remix', label: 'Remix' },
        { value: 'astro', label: 'Astro' },
    ],
    change: (e) => console.log(e.target.value)
})`
            }),

            DocSection({
                title: 'Textarea',
                description: 'This is a textarea input.',
                preview: [
                    InputPreview([
                        Textarea({
                            placeholder: 'Enter your text here...'
                        })
                    ])
                ],
                code: `
import {  EmailInput, FileInput, Input, TelInput, Textarea  } from "@base-framework/ui/atoms";

Textarea({
    placeholder: 'Enter your text here...'
})`
            }),

            DocSection({
                title: 'Time Picker',
                description: 'This is a time picker input.',
                preview: [
                    InputPreview([
                        new TimePicker({
                            selectedTime: '12:00 am',
                            change: (time) => console.log(time)
                        })
                    ])
                ],
                code: `
import TimePicker from "@base-framework/ui/molecules/date-time/time-picker.js";

new TimePicker({
    selectedTime: '12:00 am',
    change: (time) => console.log(time)
})`
            }),

            DocSection({
                title: 'Date Picker',
                description: 'This is a date picker input.',
                preview: [
                    InputPreview([
                        new DatePicker({
                            blockPriorDates: false,
                            selectedDate: '2022-01-01',
                        })
                    ])
                ],
                code: `
import DatePicker from "@base-framework/ui/molecules/date-time/date-picker.js";

new DatePicker({
    blockPriorDates: false,
    selectedDate: '2022-01-01',
})`
            }),

            DocSection({
                title: 'Range Slider',
                description: 'This is a range slider input.',
                preview: [
                    InputPreview([
                        new RangeSlider({
                            min: 0,
                            max: 100,
                            value: 50,
                            change: (value) => console.log(value)
                        })
                    ])
                ],
                code: `
import {  RangeSlider  } from "@base-framework/ui/atoms";

new RangeSlider({
    min: 0,
    max: 100,
    value: 50,
    change: (value) => console.log(value)
})`
            }),

            DocSection({
                title: 'Counter',
                description: 'This is a counter input.',
                preview: [
                    InputPreview([
                        new Counter({
                            class: 'max-w-[300px]',
                            initialCount: 5,
                            min: 0,
                            max: 100000,
                            readonly: false,
                            bind: 'count',
                            change: (value) => console.log(value)
                        })
                    ])
                ],
                code: `
import {  Counter  } from "@base-framework/ui/molecules";

new Counter({
    class: 'max-w-[300px]',
    initialCount: 5,
    min: 0,
    max: 100000,
    readonly: false,
    bind: 'count',
    change: (value) => console.log(value)
})`
            }),

            DocSection({
                title: 'Toggle Switch',
                description: 'This is a toggle switch input.',
                preview: [
                    InputPreview([
                        new Toggle({
                            active: true,
                            change: (active) => console.log(active)
                        })
                    ])
                ],
                code: `
import {  RangeSlider  } from "@base-framework/ui/atoms";

new RangeSlider({
    min: 0,
    max: 100,
    value: 50,
    change: (value) => console.log(value)
})`
            }),

            DocSection({
                title: 'Toggle Switch',
                description: 'Toggle switches to control various settings.',
                preview: [
                    Div({ class: 'p-4' }, [
                        H4({ class: 'text-lg font-bold mb-4' }, 'Email Notifications'),
                        NotificationToggle({
                            label: 'Marketing emails',
                            description: 'Receive emails about new products, features, and more.',
                            active: false,
                            onChange: (active) => console.log('Marketing emails:', active)
                        }),
                        NotificationToggle({
                            label: 'Security emails',
                            description: 'Receive emails about your account security.',
                            active: false,
                            onChange: (active) => console.log('Security emails:', active)
                        }),
                        Button({
                            text: 'Submit',
                            class: 'mt-4'
                        })
                    ])
                ],
                code: `
import {  Toggle  } from "@base-framework/ui/atoms";

const NotificationToggle = ({ label, description, active, onChange }) => (
    Div({ class: 'flex items-center justify-between p-4 border rounded-md bg-muted/10 border-muted-foreground/20 mb-4' }, [
        Div({ class: 'flex flex-col pr-8' }, [
            H4({ class: 'font-semibold' }, label),
            P({ class: 'text-sm text-muted-foreground' }, description)
        ]),
        new Toggle({
            active,
            change: onChange
        })
    ])
);

Div({ class: 'p-4' }, [
    H4({ class: 'text-lg font-bold mb-4' }, 'Email Notifications'),
    NotificationToggle({
        label: 'Marketing emails',
        description: 'Receive emails about new products, features, and more.',
        active: false,
        onChange: (active) => console.log('Marketing emails:', active)
    }),
    NotificationToggle({
        label: 'Security emails',
        description: 'Receive emails about your account security.',
        active: false,
        onChange: (active) => console.log('Security emails:', active)
    }),
    Button({
        text: 'Submit',
        class: 'mt-4'
    })
]);`
            }),

            DocSection({
                title: 'Signature Pad',
                description: 'This is a signature pad input.',
                preview: [
                    InputPreview([
                        new SignaturePanel({
                            lineWidth: 1,
                            lineColor: '#CCCCCC',
                            baseLineWidth: 1,
                            baseStrokeColor: '#333333',
                        })
                    ])
                ],
                code: `
import {  SignaturePanel  } from "@base-framework/ui/organisms";

new SignaturePanel({
    lineWidth: 1,
    lineColor: '#CCCCCC',
    baseLineWidth: 1,
    baseStrokeColor: '#333333',
})`
            }),

            P({ class: 'text-muted-foreground' }, 'The data URL image can be retrieved by calling the `getImage` method on the signature pad instance. The pad can be checked if it\'s been signed by calling the `isSigned` method on the signature pad instance.'),
        ]
    )
);

export default InputPage;