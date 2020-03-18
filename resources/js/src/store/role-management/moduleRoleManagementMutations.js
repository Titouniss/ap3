/*=========================================================================================
  File Name: moduleCalendarMutations.js
  Description: Calendar Module Mutations
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/


export default {
  SET_ROLES (state, roles) {
    state.roles = roles
  },
  REMOVE_RECORD (state, itemId) {
    const index = state.roles.findIndex((u) => u.id === itemId)
    state.roles.splice(index, 1)
  }
}
