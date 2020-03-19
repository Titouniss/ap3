/*=========================================================================================
  File Name: moduleCalendarMutations.js
  Description: Calendar Module Mutations
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/

var slug = 'companies'
var slug_singular = 'companies'

export default {
  ADD_ITEM (state, item) {
    state[slug].unshift(item)
  },
  EDIT_ITEM (state, item) {
    state[slug_singular] = item
  },
  SET_ITEMS (state, companies) {
    state[slug] = companies
  },
  REMOVE_ITEM (state, itemId) {
    const index = state[slug].findIndex((u) => u.id === itemId)
    state[slug].splice(index, 1)
  }

}
