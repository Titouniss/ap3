/*=========================================================================================
  File Name: moduleCalendarActions.js
  Description: Calendar Module Actions
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/

import axios from '@/axios.js'

export default {
  addItem({ commit }, item) {
    return new Promise((resolve, reject) => {
      axios.post("/api/project-management/store", item)
        .then((response) => {
          commit('ADD_ITEM', Object.assign(item, {id: response.data.success.id, company: response.data.success.company}))
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  },
  editItem({ commit }, item) {  
    commit('EDIT_ITEM', item)
    return
  },
  updateItem ({ commit }, item) {    
    return new Promise((resolve, reject) => {
      axios.post(`/api/project-management/update/${item.id}`,item )
        .then((response) => {          
          commit('UPDATE_ITEM', response.data.success)
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  },
  fetchItems ({ commit }) {    
    return new Promise((resolve, reject) => {
      axios.get('/api/project-management/index')
        .then((response) => {          
          commit('SET_ITEMS', response.data.success)
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  },
  fetchItem (context, id) {
    return new Promise((resolve, reject) => {
      axios.get(`/api/project-management/show/${id}`)
        .then((response) => {
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  },
  removeItem ({ commit }, id) {
    return new Promise((resolve, reject) => {
      axios.delete(`/api/project-management/${id}`)
        .then((response) => {
          commit('REMOVE_ITEM', id)
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  }
}
