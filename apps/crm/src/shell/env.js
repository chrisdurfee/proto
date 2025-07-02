const NAVIGATOR = window.navigator;
const USER_AGENT = NAVIGATOR.userAgent;
const VENDOR = NAVIGATOR.vendor;
// @ts-ignore
const USER_AGENT_DATA = NAVIGATOR.userAgentData;

/**
 * This will get the environment brand and version.
 *
 * @param {array} brands
 * @returns {object}
 */
const getBrand = (brands) =>
{
	const UNKNOWN = {
		brand: 'Unknown',
		version: '0'
	};

	if (!brands)
	{
		return UNKNOWN;
	}

	for (var i = 0, length = brands.length; i < length; i++)
	{
		var item = brands[i];
		if (item.brand.includes(';') === false)
		{
			return item;
		}
	}

	return UNKNOWN;
};

/**
 * This will get the environment settings.
 *
 * @type {object} Env
 */
export const Env =
{
	isSafari: false,
	isIos: false,
	isMobile: false,
	brand: 'Unknown',
	version: '0',
	platform: 'Unknown',
	vendor: 'Unknown',
	isTouch: false,

	/**
	 * This will setup the env settings.
	 *
	 * @returns self
	 */
	setup()
	{
		this.isSafariBrowser();
		this.setupUserAgentData();
		this.vendor = VENDOR || 'Unknown';
		this.isTouch = (NAVIGATOR.maxTouchPoints > 0);

		return this;
	},

	/**
	 * This will setup the user agent data.
	 *
	 * @returns void
	 */
	setupUserAgentData()
	{
		const data = USER_AGENT_DATA;
		if (!data)
		{
			return;
		}

		const item = getBrand(data.brands);
		this.brand = item.brand;
		this.version = item.version;

		this.isMobile = data.mobile;
		this.platform = data.platform;
	},

	/**
	 * This will get the vendor.
	 *
	 * @returns {string}
	 */
	getVendor()
	{
		return this.vendor;
	},

	/**
	 * This will check if the browser is chrome.
	 *
	 * @returns {boolean}
	 */
	isChromeBrower()
	{
		return (/Chrome/.test(USER_AGENT) && /Google Inc/.test(VENDOR));
	},

	/**
	 * This will check if the browser is safari.
	 *
	 * @returns {void}
	 */
	isSafariBrowser()
	{
		if (this.isChromeBrower())
		{
			return;
		}

		if (navigator && /Safari/.test(USER_AGENT) && /Chrome/.test(USER_AGENT))
		{
			this.isSafari = true;

			if (/iPhone|iPad/.test(USER_AGENT))
			{
				this.isIos = true;
			}
		}
	}
}.setup();