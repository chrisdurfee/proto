/**
 * This will map the mobile options.
 *
 * @param {array} options
 * @param {array} mobileOptions
 * @param {function} callBack
 * @returns {void}
 */
const mapMobileOptions = (options, mobileOptions, callBack) =>
{
	if (!options || !options.length)
	{
		return;
	}

	options.forEach(option =>
	{
		if (option.options)
		{
			mapMobileOptions(option.options, mobileOptions, callBack);
			return;
		}

		/**
		 * We also want to add a callBack to ignore the hover to the main options.
		 */
		option.callBack = callBack;
		if (option.mobileOrder !== undefined)
		{
			mobileOptions.push(option);
		}
	});
};

/**
 * This will sort the mobile options.
 *
 * @param {array} options
 * @returns {array}
 */
const sortMobileOptions = (options) => options.sort((a, b) => a.mobileOrder - b.mobileOrder);

/**
 * This will get the mobile options.
 *
 * @param {array} options
 * @param {function} callBack
 * @returns {object}
 */
export const getMobileOptions = (options, callBack) =>
{
	const mobileOptions = [];
	mapMobileOptions(options, mobileOptions, callBack);

	/**
	 * This will sort the options by the mobile order.
	 */
	return sortMobileOptions(mobileOptions);
};