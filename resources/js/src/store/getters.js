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
        if (state.windowWidth >= 1200) return 'xl'
        else if (state.windowWidth >= 992) return 'lg'
        else if (state.windowWidth >= 768) return 'md'
        else if (state.windowWidth >= 576) return 'sm'
        else return 'xs'
    },
    scrollbarTag: state => {
        return state.is_touch_device ? 'div' : 'VuePerfectScrollbar'
    },
    AppActiveUser: state => state.AppActiveUser,
    userPermissions: state => {
        const user = state.AppActiveUser
        let userPermissions = []
        if (user && user.id !== null) {
            let userPermissionsMultiple = user.roles.reduce((acc, role) => {
                if (!acc) acc = []
                acc.push(role.permissions) // get role permmissions in 1 list
                return acc
            }, [])
            userPermissions = [...new Set(userPermissionsMultiple)][0]; // get unique only
        }
        return userPermissions
    },
    userHasPermissionTo: state => permName => {
        const user = state.AppActiveUser
        let userPermissions = []
        if (user && user.id !== null) {
            let userPermissionsMultiple = user.roles.reduce((acc, role) => {
                if (!acc) acc = []
                acc.push(role.permissions) // get role permmissions in 1 list
                return acc
            }, [])
            userPermissions = [...new Set(userPermissionsMultiple)][0]; // get unique only
        }
        return userPermissions.findIndex(p => p.name === permName) > -1;
    }
}

export default getters
