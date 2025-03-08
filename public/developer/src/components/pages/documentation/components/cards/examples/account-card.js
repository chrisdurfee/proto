import { Div, Input, Span } from "@base-framework/atoms";
import { Button, Card, Icon } from "@base-framework/ui/atoms";
import { Icons } from "@base-framework/ui/icons";
import { Form, FormField } from "@base-framework/ui/molecules";
import { CardHeader } from "./card-atoms.js";

/**
 * SocialSignIn
 *
 * Creates social sign-in buttons for Github and Google.
 *
 * @returns {object}
 */
const SocialSignIn = () => (
    Div({ class: "flex items-center justify-center gap-4" }, [
        Button({
            variant: 'outline',
            class: "gap-2 w-full",
            click: () => console.log("Sign in with Github"),
            "aria-label": "Sign in with Github"
        }, [
            Icon(Icons.companies.gitHub || ''),
            Span("Github")
        ]),
        Button({
            variant: 'outline',
            class: "gap-2 w-full",
            click: () => console.log("Sign in with Google"),
            "aria-label": "Sign in with Google"
        }, [
            Icon(Icons.companies.google || ''),
            Span("Google")
        ])
    ])
);

/**
 * AccountForm
 *
 * Creates a form with email and password fields for account creation.
 *
 * @returns {object}
 */
const AccountForm = () => (
    Form({ class: 'space-y-4', submit: (e) => { e.preventDefault(); console.log("Account created"); } }, [
        // Email field
        new FormField({
            name: "email",
            label: "Email",
        }, [
            Input({
                type: "email",
                placeholder: "m@example.com",
                required: true,
                class: "border p-2 rounded-md w-full bg-background placeholder-muted-foreground text-foreground"
            })
        ]),

        // Password field
        new FormField({
            name: "password",
            label: "Password",
        }, [
            Input({
                type: "password",
                placeholder: "Your password",
                required: true,
                class: "border p-2 rounded-md w-full bg-background placeholder-muted-foreground text-foreground"
            })
        ]),

        // Submit button
        Button({
            type: "submit",
            class: "w-full mt-4 py-2 bg-primary text-primary-foreground rounded-md font-medium hover:bg-primary/90 transition"
        }, "Create account")
    ])
);

/**
 * AccountCard
 *
 * This component creates a card for account creation, including social sign-in options.
 *
 * @returns {object}
 */
export const AccountCard = () => (
    Card({ class: "w-full max-w-md mx-auto p-8 bg-card space-y-6" }, [
        // Header with title and description
        CardHeader({ title: "Create an account", description: "Enter your email below to create your account" }),

        // Social sign-in buttons
        SocialSignIn(),

        // Divider for "OR CONTINUE WITH"
        Div({ class: "relative" }, [
            Div({ class: "absolute inset-0 flex items-center" }, [
                Span({ class: "flex-grow border-t" })
            ]),
            Div({ class: 'relative flex justify-center text-xs uppercase' }, [
                Span({ class: 'bg-background px-2 text-muted-foreground' }, "or continue with")
            ]),
        ]),

        // Account creation form
        AccountForm()
    ])
);

export default AccountCard;
