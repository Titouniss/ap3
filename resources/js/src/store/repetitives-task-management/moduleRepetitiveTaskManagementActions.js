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
    return commit('ADD_ITEM', item)
  },
  editItem({ commit }, item) {  
    return commit('EDIT_ITEM', item)
  },
  fetchItemsByRange({ commit }, rangeId) {    
    return new Promise((resolve, reject) => {
      axios.get(`/api/repetitive-task-management/range/${rangeId}`)
        .then((response) => {   
          let items = response.data.success
          items.forEach(item => {
            let skill_ids = []
            if(item.skills.length > 0){
              item.skills.forEach(element => {
                skill_ids.push(element.id)
              });
            }
            item.skills = skill_ids
          });

          commit('SET_ITEMS', items)
          resolve(items)
        })
        .catch((error) => { reject(error) })
    })
  },
  updateItem ({ commit }, item) {          
    commit('UPDATE_ITEM', item)
  },
  removeItem ({ commit }, id) {
    commit('REMOVE_ITEM', id)
  },
  cleanItems ({ commit }) {           
    commit('CLEAN_ITEMS')
  },
}
