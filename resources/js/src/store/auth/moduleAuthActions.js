/*=========================================================================================
  File Name: moduleAuthActions.js
  Description: Auth Module Actions
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/

import jwt from '../../http/requests/auth/jwt/index.js'
import router from '@/router'
import moment from 'moment'
import axios from '@/axios.js'
export default {
  updateUsername ({ commit }, payload) {
    payload.user.updateProfile({
      displayName: payload.displayName
    }).then(() => {

      // If username update is success
      // update in localstorage
      const newUserData = Object.assign({}, payload.user.providerData[0])
      newUserData.displayName = payload.displayName
      commit('UPDATE_USER_INFO', newUserData, {root: true})

      // If reload is required to get fresh data after update
      // Reload current page
      if (payload.isReloadRequired) {
        router.push(router.currentRoute.query.to || '/')
      }
    }).catch((err) => {
      payload.notify({
        time: 8800,
        title: 'Error',
        text: err.message,
        iconPack: 'feather',
        icon: 'icon-alert-circle',
        color: 'danger'
      })
    })
  },
  // JWT
  loginJWT ({ commit }, payload) {
    return new Promise((resolve, reject) => {
      jwt.login(payload.userDetails.email, payload.userDetails.password)
        .then(response => {  
          const data = response.data               
          // If there's user data in response           
          if (data && data.success) {
            // Set accessToken
            if (data.userData) {
              // Update user details              
              commit('UPDATE_USER_INFO', data.userData, {root: true})
            }
            if (data.success.token) {
              // Set bearer token in axios
              commit('SET_BEARER', data.success.token)
            }
            localStorage.setItem('loggedIn', true)
            localStorage.setItem('token', data.success.token)
            localStorage.setItem('tokenExpires', moment(data.success.tokenExpires).unix() || moment().unix())
            // Navigate User to homepage
            router.push(router.currentRoute.query.to || '/')
            resolve(response)
          } else if (data && data.verify === false) {
            reject({ message: 'Veuillez valider votre adresse e-mail avant de vous connecter.',activeResend: true })
          } else {
            reject({message: 'Connexion impossible l’identifiant ou le mot de passe est incorrect.'})
          }
        })
        .catch(error => { 
          let message = 'Connexion au serveur impossible, Veuillez réessayer ultérieurement.'
          let activeResend = false
          if (error.response.data.message === "Your email address is not verified." ) {
            activeResend = true
            message = 'Veuillez valider votre adresse e-mail avant de vous connecter.'
          }
         reject({message: message, activeResend: activeResend}) })
    })
  },
  registerUserJWT ({ commit }, payload) {

    const { firstname, lastname, email, password, confirmPassword , isTermsConditionAccepted} = payload.userDetails

    return new Promise((resolve, reject) => {

      jwt.registerUser(firstname,lastname, email, password, confirmPassword,isTermsConditionAccepted)
        .then(response => {
          const data = response.data
          // Redirect User
          if (data && data.success) {
            localStorage.setItem('loggedIn', true)
            localStorage.setItem('token', data.success.token)
            localStorage.setItem('tokenExpires', data.success.tokenExpires || new Date())
            commit('SET_BEARER', data.success.token)
            commit('UPDATE_USER_INFO', data.userData, {root: true})
            // Update data in localStorage
            router.push(router.currentRoute.query.to || '/')
          }
          resolve(response)
        })
        .catch(error => { reject(error) })
    })
  },
  updateUserJWT ({ commit }, payload) {

    const { firstname, lastname, email, password, confirmPassword , isTermsConditionAccepted} = payload.userDetails

    return new Promise((resolve, reject) => {

      jwt.registerUserWithToken(firstname,lastname,email, password, confirmPassword,isTermsConditionAccepted, token)
        .then(response => {
          const data = response.data
          // Redirect User
          if (data && data.success) {
            localStorage.setItem('loggedIn', true)
            localStorage.setItem('token', data.success.token)
            localStorage.setItem('tokenExpires', data.success.tokenExpires || new Date())
            commit('SET_BEARER', data.success.token)
            commit('UPDATE_USER_INFO', data.userData, {root: true})
            // Update data in localStorage
            router.push(router.currentRoute.query.to || '/')
          }
          resolve(response)
        })
        .catch(error => { reject(error) })
    })
  },
  fetchAccessToken () {
    return new Promise((resolve) => {
      jwt.refreshToken().then(response => { resolve(response) })
    })
  },
  fetchItemWithToken (context, token) {
    return new Promise((resolve, reject) => {
      axios.get(`/api/auth/user/registration/${token}`)
        .then((response) => {
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  },
  forgotPassword ({ commit }, payload) {    
    return new Promise((resolve) => {
      jwt.forgotPassword(payload.email)
      .then(response => {resolve(response) })
      .catch(error => { reject(error)})
    })
  },
  resetPassword ({ commit }, payload) {
    return new Promise((resolve, reject) => {
      jwt.resetPassword(payload.email, payload.password, payload.c_password, payload.token)
      .then(response => { resolve(response) })
      .catch(error => { reject(error)})
    })
  },
  logoutJWT ({ commit }) {
    return new Promise((resolve, reject) => {
      jwt.logout()
        .then(response => {  
          commit('CLEAN_USER_INFO', {}, {root: true})
          localStorage.removeItem('loggedIn')
          localStorage.removeItem('token')
          localStorage.removeItem('tokenExpires')
          localStorage.removeItem('userInfo')          
          resolve(response)
        })
        .catch(error => { 
          let message = 'Connexion au serveur impossible, Veuillez réessayer ultérieurement.'
         reject({message: message}) })
    })
  },
  verify ({ commit },payload) {
    return new Promise((resolve, reject) => {
      jwt.verify(payload.email)
        .then(response => {           
          resolve(response)
        })
        .catch(error => { 
          let message = 'Connexion au serveur impossible, Veuillez réessayer ultérieurement.'
          if (error.response.data.message === "Your email address is not verified." ) {
            message = 'Veuillez valider votre adresse e-mail avant de vous connecter.'
          }
         reject({message: message}) })
    })
  },
}
