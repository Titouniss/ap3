/*=========================================================================================
  File Name: moduleCalendarGetters.js
  Description: Calendar Module Getters
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/


export default {
  getItem: state => id => state.roles.find((item) => item.id === id),
  getItems: state => state.roles,
  getPermissions: state => id => {
    const role = state.roles.find((item) => item.id === id)
    let permissions = []
    
    if (role && role.permissions.length) {
      role.permissions.forEach(permission => {
        permissions.push(permission.id)
      });
    }
    return permissions
  }
}