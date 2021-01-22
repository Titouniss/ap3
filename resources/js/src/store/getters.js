/*=========================================================================================
  File Name: getters.js
  Description: Vuex Store - getters
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/

// added so later we can keep breakpoint in sync automatically using this config file
// import tailwindConfig from "../../tailwind.config.js"

const getters = {
    // COMPONENT
    // vx-autosuggest
    // starredPages: state => state.navbarSearchAndPinList.data.filter((page) => page.highlightAction),
    windowBreakPoint: state => {
        // This should be same as tailwind. So, it stays in sync with tailwind utility classes
        if (state.windowWidth >= 1200) return "xl";
        else if (state.windowWidth >= 992) return "lg";
        else if (state.windowWidth >= 768) return "md";
        else if (state.windowWidth >= 576) return "sm";
        else return "xs";
    },
    scrollbarTag: state => {
        return state.is_touch_device ? "div" : "VuePerfectScrollbar";
    },
    AppActiveUser: state => state.AppActiveUser,
    AppActiveUserPermissions: state =>
        state.AppActiveUser ? state.AppActiveUser.permissions : [],
    userHasPermissionTo: state => permName => {
        return (
            state.AppActiveUser &&
            state.AppActiveUser.permissions &&
            state.AppActiveUser.permissions.findIndex(
                p => p.name === permName
            ) > -1
        );
    },
    module: state => state.AppActiveUser.module,
    moduleUsesSlug: state => slug =>
        state.AppActiveUser.module &&
        state.AppActiveUser.module.module_data_types &&
        state.AppActiveUser.module.module_data_types.findIndex(
            mdt => mdt.data_type.slug === slug
        ) > -1
};

export default getters;
