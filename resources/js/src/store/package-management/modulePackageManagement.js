/*=========================================================================================
  File Name:
  Description:
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/


import state from './modulePackageManagementState.js'
import mutations from './modulePackageManagementMutations.js'
import actions from './modulePackageManagementActions.js'
import getters from './modulePackageManagementGetters.js'

export default {
  isRegistered: false,
  namespaced: true,
  state,
  mutations,
  actions,
  getters
}

