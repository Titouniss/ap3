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
    state.users.unshift(item)
  },
  EDIT_ITEM (state, item) {
    state.user = item
  },
  SET_ITEMS (state, users) {
    state.users = users
  },
  UPDATE_ITEM (state, item) {
    const index = state.users.findIndex((r) => r.id === item.id)    
    Object.assign(state.users[index], item)
  },
  REMOVE_RECORD (state, itemId) {
    const index = state.users.findIndex((u) => u.id === itemId)
    state.users.splice(index, 1)
  }
}
