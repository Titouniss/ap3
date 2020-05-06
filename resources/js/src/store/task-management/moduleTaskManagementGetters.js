/*=========================================================================================
  File Name: moduleCalendarGetters.js
  Description: Calendar Module Getters
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/
import moment from 'moment'

export default {
  getItem: state => id => {
    let item = {...state.tasks.find((item) => item.id === id)}
    let skill_ids = []
    let task_ids = []

    if(item.skills.length > 0){
      item.skills.forEach(element => {
        skill_ids.push(element.id)
      });
    }
    item.skills = skill_ids
    
    if(item.previous_tasks.length > 0){
      item.previous_tasks.forEach(element => {
        task_ids.push(element.previous_task_id)
      });
    }
    item.previous_tasks = task_ids
    item.date = moment(item.date).format('DD-MM-YYYY HH:mm')

    return item
  },
}
