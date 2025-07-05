/**
 * @const {string} APP_STATE
 *
 * Application state identifier for global state management.
 */
export const APP_STATE = 'app';

/**
 * @const {string} STATE_ATTR
 * Attribute key for tracking user status.
 */
export const STATE_ATTR = 'userStatus';

/**
 * @const {object} STATES
 *
 * Possible states for a user.
 * - AWAY: User is inactive/idle.
 * - ONLINE: User is active.
 * - BUSY: User is active but marked busy.
 * - OFFLINE: User is not active.
 */
export const STATES = {
	AWAY: 'away',
	ONLINE: 'online',
	BUSY: 'busy',
	OFFLINE: 'offline',
};
