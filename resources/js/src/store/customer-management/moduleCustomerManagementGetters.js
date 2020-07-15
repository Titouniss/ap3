/*=========================================================================================
  File Name: moduleCustomerGetters.js
  Description: Customer Module Getters
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/


export default {
  getItem: state => id => state.customers.find((item) => item.id === id)
}
