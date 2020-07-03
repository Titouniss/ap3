/*=========================================================================================
  File Name: moduleDealingHoursMutations.js
  Description: Dealing Hours Module Mutations
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/


export default {
  ADD_ITEM(state, item) {
    state.dealingHours.unshift(item)
  },
  EDIT_ITEM(state, item) {
    state.dealingHour = item
  },
  SET_DEALING_HOUR(state, dealingHours) {
    state.dealingHours = dealingHours
  },
  UPDATE_ITEM(state, item) {
    const index = state.dealingHours.findIndex((r) => r.id === item.id)
    Object.assign(state.dealingHours[index], item)
  },
  REMOVE_RECORD(state, itemId) {
    const index = state.dealingHours.findIndex((u) => u.id === itemId)
    state.dealingHours.splice(index, 1)
  }
}
