/*=========================================================================================
  File Name: moduleUserManagement.js
  Description: Calendar Module
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/


import state from './moduleUnavailabilityManagementState.js'
import mutations from './moduleUnavailabilityManagementMutations.js'
import actions from './moduleUnavailabilityManagementActions.js'
import getters from './moduleUnavailabilityManagementGetters.js'

export default {
    isRegistered: false,
    namespaced: true,
    state,
    mutations,
    actions,
    getters
}

