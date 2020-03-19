/*=========================================================================================
  File Name: 
  Description: 
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/


import state from './moduleSkillManagementState.js'
import mutations from './moduleSkillManagementMutations.js'
import actions from './moduleSkillManagementActions.js'
import getters from './moduleSkillManagementGetters.js'

export default {
  isRegistered: false,
  namespaced: true,
  state,
  mutations,
  actions,
  getters
}

