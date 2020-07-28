import axios from '@/axios.js'

export default {
  addItem({ commit }, item) {
    return new Promise((resolve, reject) => {
      axios.post("/api/range-management/store", item)
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
  editItem({ commit }, item) {
    commit('EDIT_ITEM', item)
    return
  },
  fetchItems({ commit }) {
    return new Promise((resolve, reject) => {
      axios.get('/api/range-management/index')
        .then((response) => {
          commit('SET_RANGES', response.data.success)
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  },
  fetchItem(context, id) {
    return new Promise((resolve, reject) => {
      axios.get(`/api/range-management/show/${id}`)
        .then((response) => {
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  },
  updateItem({ commit }, payload) {
    return new Promise((resolve, reject) => {
      axios.post(`/api/range-management/update/${payload.id}`, payload)
        .then((response) => {
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  },
  restoreItem({ commit }, id) {
    return new Promise((resolve, reject) => {
      axios.put(`/api/range-management/restore/${id}`)
        .then((response) => {
          commit('UPDATE_ITEM', Object.assign({}, response.data.success))
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  },
  removeRecord({ commit }, id) {
    return new Promise((resolve, reject) => {
      axios.delete(`/api/range-management/destroy/${id}`)
        .then((response) => {
          commit('UPDATE_ITEM', Object.assign({}, response.data.success))
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  },
  forceRemoveRecord({ commit }, id) {
    return new Promise((resolve, reject) => {
      axios.delete(`/api/range-management/forceDelete/${id}`)
        .then((response) => {
          commit('REMOVE_RECORD', id)
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  }
}
