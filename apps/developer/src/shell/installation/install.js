import { InstallPrompt } from "../prompts/installation-prompt.js";

let deferredPrompt = null;

/**
 * @type {string} STORAGE_KEY
 * @constant
 */
const STORAGE_KEY = 'installPrompt';

/**
 * Checks if the app is already installed.
 *
 * @returns {boolean} True if the app is installed, false otherwise.
 */
const isPwaInstalled = () =>
{
	// Check for standalone mode (most platforms)
	// @ts-ignore
	return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone; // For iOS
};

/**
 * This will add the install event listener to the window.
 */
window.addEventListener('beforeinstallprompt', (e) =>
{
	// If we've already shown the prompt or the user dismissed/installed before, skip.
	if (localStorage.getItem(STORAGE_KEY) === 'true')
	{
		return;
	}

	// Prevent the default mini-infobar from appearing on mobile
	e.preventDefault();
	// Stash the event so we can use it later.
	deferredPrompt = e;
});

/**
 * This will open the install prompt.
 *
 * @returns {void}
 */
export const openInstallPrompt = () =>
{
	 // If localStorage says user previously dismissed or installed.
	if (localStorage.getItem(STORAGE_KEY) === 'true' || isPwaInstalled())
	{
		return;
	}

	const setToStorage = () => localStorage.setItem(STORAGE_KEY, 'true');
	InstallPrompt({
		promptEvent: deferredPrompt,
		onInstall: setToStorage,
		onCancel: setToStorage,
		onClose: setToStorage
	}).open();
};