/*=========================================================================================
  File Name: moduleCalendarGetters.js
  Description: Calendar Module Getters
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/


export default {
  getItem: state => id => {
    return state.projects.find((item) => item.id === id)
  },
  getCompany: state => id => {
    let skill = state.skills.find((item) => item.id === id)
    return skill ? skill.company_id : 0
  }
}
