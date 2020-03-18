/*=========================================================================================
  File Name: moduleCalendarMutations.js
  Description: Calendar Module Mutations
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/


export default {
  ADD_COMPANY (state, item) {
    state.companies.unshift(item)
  },
  SET_COMPANIES (state, companies) {
    state.companies = companies
  }
}
