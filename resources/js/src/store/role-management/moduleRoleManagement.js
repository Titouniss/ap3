/*=========================================================================================
  File Name: moduleRoleManagement.js
  Description: Calendar Module
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/


import state from './moduleRoleManagementState.js'
import mutations from './moduleRoleManagementMutations.js'
import actions from './moduleRoleManagementActions.js'
import getters from './moduleRoleManagementGetters.js'

export default {
  isRegistered: false,
  namespaced: true,
  state,
  mutations,
  actions,
  getters
}

