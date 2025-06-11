import { Div, P } from "@base-framework/atoms";
import { Button, Icon } from "@base-framework/ui/atoms";
import { Icons } from "@base-framework/ui/icons";
import { Dialog } from "@base-framework/ui/molecules";
import { Configs } from "../../configs.js";

/**
 * This will get the step for the install prompt.
 *
 * @param {object} props
 * @returns {object}
 */
const Step = (props) => (
	Div({ class: 'flex items-center gap-2' }, [
		P({ class: 'text-sm' }, props.step),
		props.icon && Icon(props.icon)
	])
);

/**
 * This will get the iOS instructions for the install prompt.
 *
 * @returns {object}
 */
const iOSInstructions = () =>
	Div({ class: 'space-y-4' }, [
		Div({ class: 'border-t my-2' }),
		Step({ step: '1. Tap the Share button', icon: Icons.apple.share }),
		Step({ step: '2. Select "Add to Home Screen"', icon: Icons.circlePlus }),
	]);

/**
 * This will get the description for the install prompt.
 *
 * @param {boolean} isIOSFallback
 * @returns {string}
 */
const getDescription = (isIOSFallback) =>
{
	return `Add ${Configs.APP_NAME} to your home screen for quick and easy access.`;
};

/**
 * This will get the buttons for the install prompt.
 *
 * @param {object} props
 * @returns {array}
 */
const PromptButtons = (props) =>
{
	const { promptEvent, onCancel, onInstall } = props;
	const isIOSFallback = !promptEvent;

	return [
		Button({ variant: 'outline', click: (e, parent) => parent.close() }, 'Not Now'),
		(!isIOSFallback) && Button({
			variant: 'primary',
			click: (e, parent) =>
			{
				promptEvent.prompt();
				promptEvent.userChoice.then((choiceResult) =>
				{
					if (choiceResult.outcome === 'accepted')
					{
						onInstall();
					}
					else
					{
						onCancel();
					}
					parent.close();
				});
			}
		}, 'Add to homescreen')
	];
};

/**
 * InstallPrompt
 *
 * A dialog that handles both Android/Chrome/Windows “Install App” (via `beforeinstallprompt`)
 * and iOS fallback instructions.
 *
 * @param {object} props
 * @returns {Dialog}
 */
export const InstallPrompt = (props) =>
{
	const { onClose } = props;
	const promptEvent = props.promptEvent || null;
	const isIOSFallback = !promptEvent;

	return new Dialog({
		icon: Icons.download,
		title: `Install ${Configs.APP_NAME}`,
		description: getDescription(isIOSFallback),
		size: 'sm',

		/**
		 * This will get the buttons for the modal.
		 *
		 * @returns {array}
		 */
		getButtons()
		{
			return PromptButtons(props);
		},
		onClose
	}, [
		isIOSFallback && iOSInstructions()
	]);
}