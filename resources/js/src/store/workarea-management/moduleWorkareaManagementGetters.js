/*=========================================================================================
  File Name: moduleCalendarGetters.js
  Description: Calendar Module Getters
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/


export default {
  getItem: state => id => state.workareas.find((item) => item.id === id),
  getCompany: state => id => {
    let workarea = state.workareas.find((item) => item.id === id)
    return workarea ? workarea.company_id : 0
  }
}
