import { A, Header } from "@base-framework/atoms";
import { Atom } from "@base-framework/base";
/**
 * This will create a logo.
 *
 * @param {object} props
 * @returns {object}
 */
const Logo = Atom((props, children) => (
    A({
        class: 'logo w-[24px] h-[24px] block',
        href: './',
        ...props,
        children
    })
));

/**
 * @type {number} prevScroll
 */
let prevScroll = 0;

/**
 * This will check when the document is scrolled.
 *
 * @returns {void}
 */
const scrollCheck = (e, parent) =>
{
    const currentScroll = window.scrollY;
    const HEADER_HEIGHT = 48;

    if (currentScroll > HEADER_HEIGHT && currentScroll > prevScroll)
    {
        parent.state.hideHeader = true;
    }
    else if (currentScroll <= HEADER_HEIGHT || currentScroll < prevScroll)
    {
        parent.state.hideHeader = false;
    }

    prevScroll = currentScroll;
};

/**
 * This will create the mobile header.
 *
 * @param {object} props
 * @returns {object}
 */
export const MobileHeader = (props = {}) => (
    Header(
        {
            class: 'mobile-header flex flex-row justify-between sm:hidden pb-3 px-3 fixed top-0 left-0 right-0 z-10 bg-background transition-all duration-200',

            /**
             * This will add the hide header state to the parent
             * component.
             */
            addState: () => { hideHeader: false },

            /**
             * This will add the hide header state to element
             * when the hide-header state is true.
             */
            onState: ['hideHeader', { 'hide-header': true }],

            /**
             * This will check when the document is scrolled
             * to update the hide header state.
             */
            addEvent: ['scroll', window, scrollCheck]
        },
        [
            Logo({
                src: props.src || '/images/logo.svg'
            })
        ]
    )
);