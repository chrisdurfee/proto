import { Comment } from "@base-framework/atoms";
import { PulseTimer } from "./pulse-timer.js";

/**
 * This will set up a timer to check user
 * authentication every 15 minutes.
 */
const FIFTEEN_MINUTES = 15 * 60 * 1000;
const timer = new PulseTimer(FIFTEEN_MINUTES);

/**
 * Heartbeat
 *
 * This class is responsible for managing the heartbeat mechanism
 *
 * It periodically checks if the user is still authenticated and
 * logs them out if their session has expired.
 */
export const Heartbeat = () => (
    Comment({
        textContent: 'Heartbeat',
        onCreated()
        {
            timer.start();
        },
        onDestroy()
        {
            timer.stop();
        }
    })
);