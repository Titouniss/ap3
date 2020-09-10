/*=========================================================================================
  File Name: moduleCalendarActions.js
  Description: Calendar Module Actions
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/

import axios from "@/axios.js";

export default {
  addItem({ commit }, item) {
    return new Promise((resolve, reject) => {
      axios.post("/api/task-management/store", item)
        .then((response) => {
          if(response.data.success){
            commit('ADD_ITEM', Object.assign( response.data.success, { id: response.data.success.id }))
          }
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  },
  addComment({ commit }, item) {
    return new Promise((resolve, reject) => {
      axios.post(`/api/task-management/store-comment/${item.id}`, item)
        .then((response) => {
          commit('UPDATE_ITEM', Object.assign(item, { comments: response.data.success.comments, comment: null }))
          resolve(response.data.success.comments)
        })
        .catch((error) => { reject(error) })
    })
  },
  addItemRange({ commit }, item) {
    return new Promise((resolve, reject) => {
      axios.post(`/api/project-management/store-range/${item.rangeId}`, item)
        .then((response) => {
          console.log(response)
          commit('SET_ITEMS', response.data.success)
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
      axios.post(`/api/task-management/update/${item.id}`, item)
      .then((response) => {
        if(response.data.success){
          commit('UPDATE_ITEM', response.data.success)
        }
        resolve(response)
      })
      .catch((error) => { reject(error) })
    })
  },
  fetchItems({ commit }) {
    return new Promise((resolve, reject) => {
      axios.get(`/api/task-management/index`)
        .then((response) => {
          commit('SET_ITEMS', response.data.success)
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  },
  fetchItemsByBundle({ commit }, bundle_id) {
    return new Promise((resolve, reject) => {
      axios.get(`/api/task-management/bundle/${bundle_id}`)
        .then((response) => {
          commit('SET_ITEMS', response.data.success)
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  },
  fetchItemsBySkill({ commit }, skill_id) {
    return new Promise((resolve, reject) => {
      axios.get(`/api/task-management/skill/${skill_id}`)
        .then((response) => {
          resolve(response)
          reject({ message: response.data.error })
        })
        .catch((error) => { console.log(error.response); reject(error) })
    })
  },
  fetchItemsBySkills({ commit }, items) {
    console.log(["1", items]);
    return new Promise((resolve, reject) => {
      axios.post(`/api/task-management/skills`, items)
        .then((response) => {
          console.log(["2", response]);
          if (response.data && response.data.success) {
            resolve(response)
          }
          reject({ message: response.data.error })
        })
        .catch((error) => { console.log(error.response); reject(error) })
    })
  },
  fetchItem(context, id) {
    return new Promise((resolve, reject) => {
      axios.get(`/api/task-management/show/${id}`)
        .then((response) => {
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  },
  removeItem({ commit }, id) {
    return new Promise((resolve, reject) => {
      axios.delete(`/api/task-management/${id}`)
        .then((response) => {
          commit('REMOVE_ITEM', id)
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  }
}
