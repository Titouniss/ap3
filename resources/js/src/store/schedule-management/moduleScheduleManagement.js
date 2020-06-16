/*=========================================================================================
  File Name: moduleSchedule.js
  Description: Calendar Module
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/


import state from './moduleScheduleManagementState.js'
import mutations from './moduleScheduleManagementMutations.js'
import actions from './moduleScheduleManagementActions.js'
import getters from './moduleScheduleManagementGetters.js'

export default {
  isRegistered: false,
  namespaced: true,
  state,
  mutations,
  actions,
  getters
}
