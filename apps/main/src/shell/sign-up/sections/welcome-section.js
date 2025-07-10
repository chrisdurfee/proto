import { Section } from '@base-framework/atoms';
import { LeftPane } from '../atoms/left-pane.js';
import { RightPane } from '../atoms/right-pane.js';

/**
 * @function WelcomeSection
 * @description
 *  Creates the split layout of the welcome page:
 *    - Left side: branding/testimonial panel
 *    - Right side: sign-up intro card
 *
 * @returns {object} A Section component containing the two panes.
 */
export const WelcomeSection = () =>
(
	Section({ class: 'flex flex-auto flex-col md:flex-row' }, [
		LeftPane(),
		RightPane()
	])
);