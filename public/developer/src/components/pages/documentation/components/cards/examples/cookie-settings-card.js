import { Div, Span } from "@base-framework/atoms";
import { Button, Card } from "@base-framework/ui/atoms";
import { Toggle } from "@base-framework/ui/molecules";
import { CardHeader } from "./card-atoms.js";

/**
 * CookieToggle
 *
 * Creates a row for each cookie setting with a toggle.
 *
 * @param {object} props - The component properties.
 * @returns {object} - A toggle row element.
 */
const CookieToggle = ({ title, description, defaultState, onChange }) => (
    Div({ class: "flex items-start justify-between gap-2" }, [
        // Cookie Information
        Div({ class: "text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 flex flex-col space-y-1" }, [
            Span({ class: "font-medium text-foreground" }, title),
            Span({ class: "font-normal leading-snug text-muted-foreground" }, description),
        ]),
        // Toggle Switch
        new Toggle({
            active: defaultState,
            change: onChange
        })
    ])
);

/**
 * CookieSettingsCard
 *
 * Displays a card with cookie settings options and a save button.
 *
 * @returns {object} - The complete cookie settings card.
 */
export const CookieSettingsCard = () => (
    Card({ class: "w-full max-w-md mx-auto p-6 bg-card space-y-6" }, [
        CardHeader({ title: "Cookie Settings", description: "Manage your cookie settings here." }),

        Div({ class: 'pt-0 grid gap-6' }, [
            // Cookie Settings Options
            CookieToggle({
                title: "Strictly Necessary",
                description: "These cookies are essential in order to use the website and use its features.",
                defaultState: true,
                onChange: (active) => console.log("Strictly Necessary:", active)
            }),
            CookieToggle({
                title: "Functional Cookies",
                description: "These cookies allow the website to provide personalized functionality.",
                defaultState: false,
                onChange: (active) => console.log("Functional Cookies:", active)
            }),
            CookieToggle({
                title: "Performance Cookies",
                description: "These cookies help to improve the performance of the website.",
                defaultState: false,
                onChange: (active) => console.log("Performance Cookies:", active)
            })
        ]),

        // Save Preferences Button
        Button({
            variant: 'outline',
            class: "w-full",
            click: () => console.log("Preferences saved")
        }, "Save preferences")
    ])
);

export default CookieSettingsCard;