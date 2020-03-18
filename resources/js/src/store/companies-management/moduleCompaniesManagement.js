/*=========================================================================================
  File Name: 
  Description: 
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/


import state from './moduleCompaniesManagementState.js'
import mutations from './moduleCompaniesManagementMutations.js'
import actions from './moduleCompaniesManagementActions.js'
import getters from './moduleCompaniesManagementGetters.js'

export default {
  isRegistered: false,
  namespaced: true,
  state,
  mutations,
  actions,
  getters
}

