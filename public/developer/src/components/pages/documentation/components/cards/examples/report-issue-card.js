import { Div } from "@base-framework/atoms";
import { Button, Card, Input, Select, Textarea } from "@base-framework/ui/atoms";
import { Form, FormField } from "@base-framework/ui/molecules";
import { CardHeader } from "./card-atoms.js";

/**
 * ReportIssueForm Component
 *
 * Creates the form for reporting an issue, with fields for Area, Security Level, Subject, and Description.
 *
 * @returns {object} - The report issue form.
 */
const ReportIssueForm = () => (
    Form({ class: 'space-y-4', submit: (e) => { e.preventDefault(); console.log("Issue reported"); } }, [
        // Row for Area and Security Level
        Div({ class: "flex flex-auto w-full gap-4" }, [
            // Area field
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
                    defaultValue: "billing",
                    change: (e) => console.log(`Area selected: ${e.target.value}`)
                })
            ]),

            // Security Level field
            new FormField({ name: "security_level", label: "Security Level" }, [
                Select({
                    required: true,
                    class: "border p-2 rounded-md w-full bg-background text-foreground",
                    options: [
                        { value: "severity_1", label: "Severity 1" },
                        { value: "severity_2", label: "Severity 2" },
                        { value: "severity_3", label: "Severity 3" }
                    ],
                    defaultValue: "severity_2",
                    change: (e) => console.log(`Security Level selected: ${e.target.value}`)
                })
            ])
        ]),

        // Subject field
        new FormField({
            name: "subject",
            label: "Subject",
        }, [
            Input({
                type: "text",
                placeholder: "I need help with...",
                required: true,
                class: "border p-2 rounded-md w-full bg-background text-foreground placeholder-muted-foreground"
            })
        ]),

        // Description field
        new FormField({
            name: "description",
            label: "Description",
        }, [
            Textarea({
                placeholder: "Please include all information relevant to your issue.",
                required: true,
                class: "border p-2 rounded-md w-full bg-background text-foreground placeholder-muted-foreground"
            })
        ]),

        // Action buttons: Cancel and Submit
        Div({ class: "flex justify-end gap-4 mt-4" }, [
            Button({
                variant: 'outline',
                class: "px-4 py-2",
                click: () => console.log("Cancel clicked")
            }, "Cancel"),

            Button({
                type: "submit",
                class: "px-4 py-2 bg-primary text-primary-foreground rounded-md font-medium hover:bg-primary/90 transition"
            }, "Submit")
        ])
    ])
);

/**
 * ReportIssueCard Component
 *
 * This component renders the report issue card with a header, form fields, and action buttons.
 *
 * @returns {object} - The complete report issue card.
 */
export const ReportIssueCard = () => (
    Card({ class: "w-full max-w-md mx-auto p-6 bg-card space-y-6" }, [
        CardHeader({ title: "Report an issue", description: "What area are you having problems with?" }),

        ReportIssueForm()
    ])
);

export default ReportIssueCard;
