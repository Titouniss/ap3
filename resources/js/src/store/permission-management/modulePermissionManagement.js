/*=========================================================================================
  Description: Module 
==========================================================================================*/


import state from './modulePermissionManagementState.js'
import mutations from './modulePermissionManagementMutations.js'
import actions from './modulePermissionManagementActions.js'
import getters from './modulePermissionManagementGetters.js'

export default {
  isRegistered: false,
  namespaced: true,
  state,
  mutations,
  actions,
  getters
}

