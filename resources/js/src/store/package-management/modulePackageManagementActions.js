/*=========================================================================================
  File Name: moduleCalendarActions.js
  Description: Calendar Module Actions
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/

import axios from "@/axios.js";

export default {
    fetchItems({ commit }) {
        return new Promise((resolve, reject) => {
            axios
                .get("/api/package-management/index")
                .then(response => {
                    commit("SET_ITEMS", response.data.success);
                    resolve(response);
                })
                .catch(error => {
                    reject(error);
                });
        });
    },
};
