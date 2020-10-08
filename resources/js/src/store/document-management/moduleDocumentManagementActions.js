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
    uploadFile({ commit }, item) {
        return new Promise((resolve, reject) => {
            axios
                .post(
                    `/api/document-management/upload-file/${item.token}`,
                    item.files
                )
                .then(response => {
                    resolve(response);
                })
                .catch(error => {
                    reject(error);
                });
        });
    },
    deleteFile({ commit }, id) {
        return new Promise((resolve, reject) => {
            axios
                .delete(`/api/document-management/delete-file/${id}`)
                .then(response => {
                    resolve(response);
                })
                .catch(error => {
                    reject(error);
                });
        });
    },
    deleteFiles({ commit }, ids) {
        return new Promise((resolve, reject) => {
            axios
                .post(`/api/document-management/delete-files`, ids)
                .then(response => {
                    resolve(response);
                })
                .catch(error => {
                    reject(error);
                });
        });
    }
};
