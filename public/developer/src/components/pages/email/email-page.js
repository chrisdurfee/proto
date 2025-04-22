import { Div, Iframe, Span } from "@base-framework/atoms";
import { Data } from "@base-framework/base";
import { Input } from "@base-framework/ui/atoms";
import { BlankPage } from "@base-framework/ui/pages";

/**
 * ContentSwitch
 *
 * Displays the template preview with input.
 *
 * @param {object} props
 * @returns {object}
 */
export const ContentSwitch = (props) => (
	Div({ class: 'flex-1 flex-col w-full h-full hidden lg:flex px-6 py-4 space-y-4' }, [
		Div({ class: "flex items-center justify-between border-b border-muted pb-2" }, [
			Span({ class: "text-xl font-semibold text-foreground" }, "Email Template Preview")
		]),
		Div({ class: "flex flex-auto flex-col space-y-2" }, [
			Input({
				type: "text",
				class: "w-full text-sm px-3 py-2 border border-muted rounded-md bg-background text-foreground",
				placeholder: "Enter template name (e.g., Common\\Email\\BasicEmail)",
				bind: 'template',
			}),
			Div({ class: "flex-1 border border-muted rounded-lg overflow-hidden" }, [
				Iframe({
					src: `//proto.local/developer/app/email/preview/index.php?template=[[template]]`,
					class: "w-full h-full border-none",
					allowTransparency: true,
					allowFullScreen: true
				})
			])
		])
	])
);

/**
 * EmailPage
 *
 * This will create the email page.
 *
 * @returns {object}
 */
export const EmailPage = () =>
{
	const Props =
	{
		setData()
		{
			return new Data({
				template: 'Modules\\User\\Email\\Auth\\AuthMultiFactorEmail'
			});
		}
	};

	return new BlankPage(Props, [
		Div({ class: "flex w-full flex-col lg:flex-row h-full" }, [
			ContentSwitch()
		])
	]);
};

export default EmailPage;