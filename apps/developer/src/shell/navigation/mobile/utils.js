
/**
 * This is the types for the navigation.
 *
 * @type {object} TYPES
 */
export const TYPES = {
	PHONE: 'phone',
	TABLET: 'tablet',
	DESKTOP: 'desktop'
};

/**
 * This will check the type by size.
 *
 * @returns {string}
 */
export const checkTypeBySize = () =>
{
	const width = window.innerWidth;
	if (width >= 1024)
	{
		return TYPES.DESKTOP;
	}

	if (width >= 640)
	{
		return TYPES.TABLET;
	}

	return TYPES.PHONE;
};

/**
 * This will get the type class.
 *
 * @param {string} type
 * @returns {string}
 */
export const getTypeClass = (type) =>
{
	switch (type)
	{
		case TYPES.PHONE:
			return 'bottom: calc(56px + env(safe-area-inset-bottom));';
		case TYPES.TABLET:
			return 'left: calc(64px + env(safe-area-inset-left));';
		case TYPES.DESKTOP:
			return '';
	}
};