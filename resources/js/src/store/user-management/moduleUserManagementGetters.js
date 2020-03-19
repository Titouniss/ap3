/*=========================================================================================
  File Name: moduleCalendarGetters.js
  Description: Calendar Module Getters
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/


export default {
  getItem: state => id => state.users.find((item) => item.id === id),
  getRole: state => id => {
    let user = state.users.find((item) => item.id === id)
    return user && user.roles.length ? parseInt(user.roles[0].id) : 0 
  }

}
