/*=========================================================================================
  File Name: moduleDealingHourManagement.js
  Description: Dealing hours Module
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/


import state from './moduleRepetitiveDealingHoursManagementState.js'
import mutations from './moduleRepetitiveDealingHoursManagementMutations.js'
import actions from './moduleRepetitiveDealingHoursManagementActions.js'
import getters from './moduleRepetitiveDealingHoursManagementGetters.js'

export default {
  isRegistered: false,
  namespaced: true,
  state,
  mutations,
  actions,
  getters
}