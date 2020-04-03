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
    state.ranges.unshift(item)
  },
  EDIT_ITEM (state, item) {
    state.role = item
  },
  SET_RANGES (state, ranges) {
    state.ranges = ranges
  },
  UPDATE_ITEM (state, item) {    
    const index = state.ranges.findIndex((r) => r.id === item.id)  
    Object.assign(state.ranges[index], item)
  },
  REMOVE_RECORD (state, itemId) {
    const index = state.ranges.findIndex((u) => u.id === itemId)
    state.ranges.splice(index, 1)
  }
}
