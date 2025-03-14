import { OnState } from "@base-framework/atoms";
import { Component, Jot } from "@base-framework/base";
import { Button } from "@base-framework/ui/atoms";
import { Icons } from "@base-framework/ui/icons";
import { UserModel } from "../models/user-model.js";

/**
 * This will update the resolve status of the error.
 *
 * @param {string|number} id
 * @param {number} resolved
 * @returns {void}
 */
const updateResolveStatus = (id, resolved) =>
{
    const data = new UserModel({
        id,
        resolved
    });

    data.xhr.updateResolved('', (response) =>
    {
    });
};

/**
 * This will create a button to resolve the error.
 *
 * @param {object} props
 * @returns {object}
 */
const ResolveButton = (props) => (
	Button({
		variant: 'withIcon',
		class: 'outline',
		icon: Icons.circleCheck,
		click(e, parent)
		{
			e.preventDefault();
			e.stopPropagation();

			updateResolveStatus(props.id, 1);
			parent.state.resolved = 1;
		}
	}, 'Resolve')
);

/**
 * This will create a button to mark as unresolved.
 *
 * @param {object} props
 * @returns {object}
 */
const UnresolveButton = (props) => (
	Button({
		variant: 'withIcon',
		class: 'outline',
		icon: Icons.circleX,
		click(e, parent)
		{
			e.preventDefault();
			e.stopPropagation();

			updateResolveStatus(props.id, 0);
			parent.state.resolved = 0;
		}
	}, 'Unresolve')
);

/**
 * This will render the button.
 *
 * @type {typeof Component}
 */
export const ResultButtons = Jot(
{
	/**
	 * @type {object}
	 */
	// @ts-ignore
	state()
	{
		return {
			// @ts-ignore
			resolved: this.resolved
		};
	},

	/**
	 * This will render the button.
	 *
	 * @returns {object}
	 */
	// @ts-ignore
	render()
	{
		const props = {
			// @ts-ignore
			id: this.id,
		};

		return OnState('resolved', (resolved) => (resolved === 1 ? UnresolveButton(props) : ResolveButton(props)));
	}
});