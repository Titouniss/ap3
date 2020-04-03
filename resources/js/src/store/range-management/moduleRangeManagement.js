/*=========================================================================================
  File Name: moduleRangeManagement.js
  Description: Calendar Module
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/


import state from './moduleRangeManagementState.js'
import mutations from './moduleRangeManagementMutations.js'
import actions from './moduleRangeManagementActions.js'
import getters from './moduleRangeManagementGetters.js'

export default {
  isRegistered: false,
  namespaced: true,
  state,
  mutations,
  actions,
  getters
}

