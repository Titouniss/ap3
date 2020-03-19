import axios from '@/axios.js'

export default {
  addItem({ commit }, item) {    
    return new Promise((resolve, reject) => {
      axios.post("/api/role-management/store", item)
        .then((response) => {
          commit('ADD_ITEM', Object.assign(item, {id: response.data.id}))
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  },
  editItem({ commit }, item) {    
    commit('EDIT_ITEM', item)
    return
  },
  fetchItems ({ commit }) {    
    return new Promise((resolve, reject) => {
      axios.get('/api/role-management/index')
        .then((response) => {          
          commit('SET_ROLES', response.data.success)
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  },
  fetchRole (context, id) {
    return new Promise((resolve, reject) => {
      axios.get(`/api/role-management/show/${id}`)
        .then((response) => {
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  },
  updateItem ({ commit }, payload) {    
    return new Promise((resolve, reject) => {
      axios.post(`/api/role-management/update/${payload.id}`,payload )
        .then((response) => {          
          if (response.data && response.data.item) {            
            commit('UPDATE_ITEM', response.data.item)            
          }
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  },
  removeRecord ({ commit }, id) {
    return new Promise((resolve, reject) => {
      axios.delete(`/api/role-management/destroy/${id}`)
        .then((response) => {
          commit('REMOVE_RECORD', id)
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  }
}
