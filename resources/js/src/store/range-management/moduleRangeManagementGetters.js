/*=========================================================================================
  File Name: moduleCalendarGetters.js
  Description: Calendar Module Getters
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/


export default {
  getItem: state => id => state.ranges.find((item) => item.id === id),
  getItems: state => state.ranges,
  getItemsForCompany: state => id => state.ranges.filter(r.company_id === id),
}
