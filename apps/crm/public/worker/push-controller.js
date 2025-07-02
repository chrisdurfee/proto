/**
 * PushController
 *
 * This class is responsible for handling push notifications.
 *
 * @class
 */
class PushController
{
	/**
	 * This will create a new instance of PushController.
	 *
	 * @constructor
	 * @param {string} title
	 */
	constructor(title)
	{
		this.title = title;
		this.addEvents();
	}

	/**
	 * This will get the options for the push notification.
	 *
	 * @param {object} event
	 * @returns {object}
	 */
	getOptions(event)
	{
		const icon = './images/icons/icon-512.png',
		badge = '/images/icons/badge.png',
		data = event.data.json();

		return {
			title: data.title || null,
			body: data.message,
			icon,
			badge,
			data
		};
	}

	/**
	 * This will add the events for the push notifications.
	 *
	 * @returns {void}
	 */
	addEvents()
	{
		/**
		 * This will be called when a push notification is received.
		 */
		self.addEventListener('push', (event) =>
		{
			const options = this.getOptions(event),
			title = options.title || this.title;

			event.waitUntil(self.registration.showNotification(title, options));
		});

		/**
		 * This will be called when a notification is clicked.
		 * If the notification has a URL, it will open a new window with that URL.
		 * If the notification does not have a URL, it will open a new window with the root URL.
		 * If the notification is clicked and the window is already open, it will focus the window.
		 */
		self.addEventListener('notificationclick', (event) =>
		{
			const notification = event.notification;
			notification.close();

			event.waitUntil(
				clients.matchAll({
					type: 'window'
				})
				.then((clientList) =>
				{
					let url = notification.data.url;
					if (clientList.length > 0)
					{
						const client = clientList[0];
						if (url && client.url.includes(url) === url)
						{
							return client.focus();
						}
					}

					url = url || '/';
					return clients.openWindow(url);
				})
			);
		});
	}
}