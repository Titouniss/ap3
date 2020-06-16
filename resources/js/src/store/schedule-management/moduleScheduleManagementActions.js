/*=========================================================================================
  File Name: ScheduleActions.js
  Description: Schedule Module Actions
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/

import axios from '@/axios.js'

export default {
  addEvent({ commit }, event) {
    return new Promise((resolve, reject) => {
      axios.post('/api/apps/calendar/events/', { event })
        .then((response) => {
          commit('ADD_EVENT', Object.assign(event, { id: response.data.id }))
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  },
  addEvents({ commit }, event) {
    return commit('SET_EVENTS', event)
  },
  fetchEvents({ commit }) {
    return new Promise((resolve, reject) => {
      axios.get('/api/apps/calendar/events')
        .then((response) => {
          commit('SET_EVENTS', response.data)
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  },
  fetchEventLabels({ commit }) {
    return new Promise((resolve, reject) => {
      axios.get('/api/apps/calendar/labels')
        .then((response) => {
          commit('SET_LABELS', response.data)
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  },
  editEvent({ commit }, event) {
    commit('EDIT_EVENT', event)
    return
    // return new Promise((resolve, reject) => {
    //   axios.post(`/api/apps/calendar/event/${event.id}`, { event })
    //     .then((response) => {

    //       // Convert Date String to Date Object
    //       const event = response.data
    //       event.startDate = new Date(event.startDate)
    //       event.endDate = new Date(event.endDate)

    //       commit('UPDATE_EVENT', event)
    //       resolve(response)
    //     })
    //     .catch((error) => { reject(error) })
    // })
  },
  updateEvent({ commit }, event) {
    commit('UPDATE_EVENT', event)
    return
  },
  removeEvent({ commit }, eventId) {
    return commit('REMOVE_EVENT', eventId), commit('EDIT_EVENT', {});
    return new Promise((resolve, reject) => {
      axios.delete(`/api/apps/calendar/event/${eventId}`)
        .then((response) => {
          commit('REMOVE_EVENT', response.data)
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  },
  eventDragged({ commit }, payload) {
    return new Promise((resolve, reject) => {
      axios.post(`/api/apps/calendar/event/dragged/${payload.event.id}`, { payload })
        .then((response) => {

          // Convert Date String to Date Object
          const event = response.data
          event.startDate = new Date(event.startDate)
          event.endDate = new Date(event.endDate)

          commit('UPDATE_EVENT', event)
          resolve(response)
        })
        .catch((error) => { reject(error) })
    })
  }
}
