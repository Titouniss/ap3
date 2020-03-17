/*=========================================================================================
  File Name: moduleAuthState.js
  Description: Auth Module State
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/

export default {
  isUserLoggedIn: () => {
    let isAuthenticated = false
    let expiresAt = localStorage.getItem('tokenExpires')
    if (expiresAt && expiresAt > new Date()) isAuthenticated = true
     console.log(expiresAt);
     console.log(new Date());
     console.log(expiresAt > new Date());
     console.log(isAuthenticated);
     
     
    return localStorage.getItem('userInfo') && isAuthenticated
  }
}
