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
  fetchCompanies ({ commit }) {    
    return new Promise((resolve, reject) => {
      axios.get('/api/companies-management/companies')
        .then((response) => {
          console.log('axios.get /api/companies-management/companies');
          console.log(response);
          
          commit('SET_COMPANIES', response.data.success)
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  },
}
