/*=========================================================================================
  File Name: moduleCalendarMutations.js
  Description: Calendar Module Mutations
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/

var slug = 'companies'
var slug_singular = 'company'

export default {
  ADD_ITEM (state, item) {
    state[slug].unshift(item)
  },

  EDIT_ITEM (state, item) {
    state[slug_singular] = item
  },

  UPDATE_ITEM (state, item) {
    const index = state[slug].findIndex((r) => r.id === item.id)    
    Object.assign(state[slug][index], item)
  },

  // UPDATE_TASK (state, task) {
  //   const taskIndex = state.tasks.findIndex((t) => t.id === task.id)
  //   Object.assign(state.tasks[taskIndex], task)
  // }

  SET_ITEMS (state, companies) {
    state[slug] = companies
  },

  REMOVE_ITEM (state, itemId) {
    const index = state[slug].findIndex((u) => u.id === itemId)
    state[slug].splice(index, 1)
  }

}
