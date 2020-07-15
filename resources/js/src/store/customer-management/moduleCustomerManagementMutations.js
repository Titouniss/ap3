/*=========================================================================================
  File Name: moduleCustomerMutations.js
  Description: Customer Module Mutations
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/

var slug = 'customers'
var slug_singular = 'customer'

export default {
  ADD_ITEM(state, item) {
    state[slug].unshift(item)
  },

  EDIT_ITEM(state, item) {
    state[slug_singular] = item
  },

  UPDATE_ITEM(state, item) {
    const index = state[slug].findIndex((r) => r.id === item.id)
    state[slug].splice(index, 1, item)
  },

  SET_ITEMS(state, items) {
    state[slug] = items
  },

  REMOVE_ITEM(state, itemId) {
    const index = state[slug].findIndex((u) => u.id === itemId)
    state[slug].splice(index, 1)
  },

}
