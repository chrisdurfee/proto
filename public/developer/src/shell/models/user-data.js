import { Model } from "@base-framework/base";

/**
 * UserData Model
 *
 * This will create a model for user data.
 *
 * @type {typeof Model} UserData
 */
export const UserData = Model.extend({
    url: '/api', // API endpoint for fetching or updating user data
});
