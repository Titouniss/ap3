/*=========================================================================================
  File Name: moduleCalendarMutations.js
  Description: Calendar Module Mutations
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/

var slug = 'repetitivesTasks'
var slug_singular = 'repetitivesTask'

export default {
  ADD_ITEM (state, item) {
    const id = Math.floor(100000 + Math.random() * 900000)
    item.id = id
    state[slug].unshift(item)
    state[slug].sort(function(a,b){
      return a.order-b.order
    })
  },

  EDIT_ITEM (state, item) {
    state[slug_singular] = item
  },

  UPDATE_ITEM (state, item) {
    const index = state[slug].findIndex((r) => r.id === item.id)    
    state[slug].splice(index, 1, item)
    state[slug].sort(function(a,b){
      return a.order-b.order
    })
  },

  SET_ITEMS (state, items) {
    state[slug] = items
    state[slug].sort(function(a,b){
      return a.order-b.order
    })
  },

  REMOVE_ITEM (state, itemId) {
    const index = state[slug].findIndex((u) => u.id === itemId)
    state[slug].splice(index, 1)
  },

  CLEAN_ITEMS ( state){
    state[slug] = []
  },

}
