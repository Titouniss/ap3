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
    let item = state.workareas.find((item) => item.id === id)
    let skill_ids = []
    if(item.skills.length > 0){
      item.skills.forEach(element => {
        skill_ids.push(element.id)
      });
    }
    item.skills = skill_ids
    return item
  },
  getCompany: state => id => {
    let workarea = state.workareas.find((item) => item.id === id)
    return workarea ? workarea.company_id : 0
  }
}
