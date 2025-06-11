const match = window.matchMedia;

/**
 * This will set the html class by theme settings.
 *
 * @returns {void}
 */
export const setHtmlThemeBySettings = () =>
{
	if (!match)
	{
		return;
	}

	// if theme HAS been set and is different from OS, add classes to html tag
	const storage = window.localStorage;
	let theme = storage.getItem('theme');
	if (!theme)
	{
		return;
	}

	if (!match('(prefers-color-scheme: ' + theme + ')').matches)
	{
		const htmlTag = document.documentElement;
		htmlTag.classList.add(theme);
	}
};