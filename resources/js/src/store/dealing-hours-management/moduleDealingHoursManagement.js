/*=========================================================================================
  File Name: moduleDealingHourManagement.js
  Description: Dealing hours Module
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/


import state from './moduleDealingHoursManagementState.js'
import mutations from './moduleDealingHoursManagementMutations.js'
import actions from './moduleDealingHoursManagementActions.js'
import getters from './moduleDealingHoursManagementGetters.js'

export default {
  isRegistered: false,
  namespaced: true,
  state,
  mutations,
  actions,
  getters
}