import { Div, Iframe } from "@base-framework/atoms";
import { BlankPage } from "@base-framework/ui/pages";

/**
 * This will create the ContentSwitch.
 *
 * @param {object} props
 * @returns {object}
 */
export const ContentSwitch = (props) => (
	Div({ class: 'flex-[4] flex-col w-full h-full hidden lg:flex' }, [
		Div({ class: "w-full flex flex-auto flex-col space-y-4" }, [
			Div({ class: "flex items-center justify-between border-b pb-2" }, [

			]),
			Div({ class: "flex flex-col space-y-4" }, [
				Iframe({
					src: "/developer/app/email/preview/index.php",
					class: "w-full h-full",
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
export const EmailPage = () => (
	new BlankPage([
		Div({ class: "flex w-full flex-col lg:flex-row h-full" }, [
			ContentSwitch()
		])
	])
);

export default EmailPage;