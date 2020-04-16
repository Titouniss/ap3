/*=========================================================================================
  File Name: 
  Description: 
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/


import state from './moduleRepetitiveTaskManagementState.js'
import mutations from './moduleRepetitiveTaskManagementMutations.js'
import actions from './moduleRepetitiveTaskManagementActions.js'
import getters from './moduleRepetitiveTaskManagementGetters.js'

export default {
  isRegistered: false,
  namespaced: true,
  state,
  mutations,
  actions,
  getters
}

