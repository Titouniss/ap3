/*=========================================================================================
  File Name: moduleAuthState.js
  Description: Auth Module State
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/
import moment from 'moment'

export default {
  isUserLoggedIn: () => {
    let isAuthenticated = false
    let expiresAt = localStorage.getItem('tokenExpires')
    if (expiresAt && expiresAt > moment().unix()) isAuthenticated = true
    return localStorage.getItem('userInfo') && isAuthenticated
  }
}
