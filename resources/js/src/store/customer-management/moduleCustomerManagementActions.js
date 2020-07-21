/*=========================================================================================
  File Name: moduleCustomerActions.js
  Description: Customer Module Actions
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/

import axios from '@/axios.js'

export default {
  addItem({ commit }, item) {
    return new Promise((resolve, reject) => {
      axios.post("/api/customer-management/store", item)
        .then((response) => {
          commit('ADD_ITEM', Object.assign(item, { id: response.data.success.id }))
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  },
  editItem({ commit }, item) {
    commit('EDIT_ITEM', item)
    return
  },
  updateItem({ commit }, item) {
    return new Promise((resolve, reject) => {
      axios.post(`/api/customer-management/update/${item.id}`, item)
        .then((response) => {
          commit('UPDATE_ITEM', Object.assign({}, item))
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  },
  fetchItems({ commit }) {
    return new Promise((resolve, reject) => {
      axios.get('/api/customer-management/index')
        .then((response) => {
          commit('SET_ITEMS', response.data.success)
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  },
  fetchItem(context, id) {
    return new Promise((resolve, reject) => {
      axios.get(`/api/customer-management/show/${id}`)
        .then((response) => {
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  },
  removeItem({ commit }, id) {
    return new Promise((resolve, reject) => {
      axios.delete(`/api/customer-management/destroy/${id}`)
        .then((response) => {
          commit('REMOVE_ITEM', id)
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  },
  forceRemoveItem({ commit }, id) {
    return new Promise((resolve, reject) => {
      axios.delete(`/api/customer-management/forceDelete/${id}`)
        .then((response) => {
          commit('REMOVE_ITEM', id)
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  },
}