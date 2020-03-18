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
  // addItem({ commit }, item) {
  //   return new Promise((resolve, reject) => {
  //     axios.post("/api/data-list/products/", {item: item})
  //       .then((response) => {
  //         commit('ADD_ITEM', Object.assign(item, {id: response.data.id}))
  //         resolve(response)
  //       })
  //       .catch((error) => { reject(error) })
  //   })
  // },
  fetchRoles ({ commit }) {    
    return new Promise((resolve, reject) => {
      axios.get('/api/role-management/index')
        .then((response) => {
          console.log(response);
          
          commit('SET_ROLES', response.data.success)
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  },
  fetchUser (context, id) {
    return new Promise((resolve, reject) => {
      axios.get(`/api/role-management/roles/${id}`)
        .then((response) => {
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  },
  removeRecord ({ commit }, id) {
    return new Promise((resolve, reject) => {
      axios.delete(`/api/role-management/roles/${id}`)
        .then((response) => {
          commit('REMOVE_RECORD', id)
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  }
}
