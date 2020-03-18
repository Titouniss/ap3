/*=========================================================================================
  File Name: moduleCalendarMutations.js
  Description: Calendar Module Mutations
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/


export default {
  ADD_ITEM (state, item) {
    state.roles.unshift(item)
  },
  SET_ROLES (state, roles) {
    state.roles = roles
  },
  UPDATE_ITEM (state, item) {
    const index = state.roles.findIndex((p) => p.id === item.id)
    Object.assign(state.roles[index], item)
  },
  REMOVE_RECORD (state, itemId) {
    const index = state.roles.findIndex((u) => u.id === itemId)
    state.roles.splice(index, 1)
  }
}
