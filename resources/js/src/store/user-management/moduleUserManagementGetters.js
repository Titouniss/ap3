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
    let item = { ...state.users.find((item) => item.id === id) }
    let skill_ids = []
    if (item.skills.length > 0) {
      item.skills.forEach(element => {
        skill_ids.push(element.id)
      });
    }
    item.skills = skill_ids
    return item
  },
  getRole: state => id => {
    let user = state.users.find((item) => item.id === id)
    return user && user.roles.length ? parseInt(user.roles[0].id) : 0
  }

}
