/*=========================================================================================
  File Name: moduleDealingHoursActionrs.js
  Description: Dealing Hours Module Actions
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/
import axios from '@/axios.js'

export default {
  addItem({ commit }, item) {
    return new Promise((resolve, reject) => {
      axios.post("/api/dealing-hours-management/store", item)
        .then((response) => {
          commit('ADD_ITEM', Object.assign(item, { id: response.data.id }))
          resolve(response)
        })
        .catch((error) => {
          console.log(error);
          reject(error)
        })
    })
  },
  addOrUpdtateUsed({ commit }, item) {
    return new Promise((resolve, reject) => {
      axios.post("/api/dealing-hours-management/storeOrUpdateUsed", item)
        .then((response) => {
          if (response.data.success && response.data.success[1] === "create") {
            commit('ADD_ITEM', Object.assign(item, { id: response.data.success[0].id }))
          }
          resolve(response)
        })
        .catch((error) => {
          console.log(error);
          reject(error)
        })
    })
  },
  editItem({ commit }, item) {
    commit('EDIT_ITEM', item)
    return
  },
  fetchItems({ commit }) {
    return new Promise((resolve, reject) => {
      axios.get('/api/dealing-hours-management/index')
        .then((response) => {
          commit('SET_DEALING_HOUR', response.data.success)
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  },
  fetchItem(context, id) {
    return new Promise((resolve, reject) => {
      axios.get(`/api/dealing-hours-management/show/${id}`)
        .then((response) => {
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  },
  getOvertimes({ commit }, user_id) {
    return new Promise((resolve, reject) => {
      axios.get(`/api/dealing-hours-management/overtimes/${user_id}`)
        .then((response) => {
          commit('EDIT_ITEM', response.data.success)
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  },
  updateItem({ commit }, payload) {
    return new Promise((resolve, reject) => {
      axios.post(`/api/dealing-hours-management/update/${payload.id}`, payload)
        .then((response) => {
          resolve(response)
          
        })
        .catch((error) => { reject(error) })
    })
  },
  removeRecord({ commit }, id) {
    return new Promise((resolve, reject) => {
      axios.delete(`/api/dealing-hours-management/destroy/${id}`)
        .then((response) => {
          commit('REMOVE_RECORD', id)
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  },
  forceRemoveRecord({ commit }, id) {
    return new Promise((resolve, reject) => {
      axios.delete(`/api/dealing-hours-management/forceDelete/${id}`)
        .then((response) => {
          commit('REMOVE_RECORD', id)
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  }
}
