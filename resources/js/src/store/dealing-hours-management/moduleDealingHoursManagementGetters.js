/*=========================================================================================
  File Name: moduleDealingHoursGetters.js
  Description: Dealing Hours Module Getters
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/
import moment from 'moment'

export default {
  getItem: state => id => {
    return { ...state.dealingHours.find((item) => item.id === id) }
  },
}
