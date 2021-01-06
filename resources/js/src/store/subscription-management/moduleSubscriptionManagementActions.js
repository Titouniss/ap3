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
                .get("/api/subscription-management/index")
                .then(response => {
                    commit("SET_ITEMS", response.data.success);
                    resolve(response);
                })
                .catch(error => {
                    reject(error);
                });
        });
    },
    fetchPackages({ commit }) {
        return new Promise((resolve, reject) => {
            axios
                .get("/api/subscription-management/packages")
                .then(response => {
                    commit("SET_PACKAGES", response.data.success);
                    resolve(response);
                })
                .catch(error => {
                    reject(error);
                });
        });
    },
    fetchItemsByCompany({ commit }, company_id) {
        return new Promise((resolve, reject) => {
            axios
                .get(`/api/subscription-management/company/${company_id}`)
                .then(response => {
                    commit("SET_ITEMS", response.data.success);
                    resolve(response);
                })
                .catch(error => {
                    reject(error);
                });
        });
    },
    fetchItem(context, id) {
        return new Promise((resolve, reject) => {
            axios
                .get(`/api/subscription-management/show/${id}`)
                .then(response => {
                    resolve(response);
                })
                .catch(error => {
                    reject(error);
                });
        });
    },
    addItem({ commit }, item) {
        return new Promise((resolve, reject) => {
            axios
                .post("/api/subscription-management/store", item)
                .then(response => {
                    if (response.data.success) {
                        commit(
                            "ADD_ITEM",
                            Object.assign(response.data.success, {
                                id: response.data.success.id
                            })
                        );
                        resolve(response);
                    }

                    reject({ message: response.data.error });
                })
                .catch(error => {
                    reject(error);
                });
        });
    },
    editItem({ commit }, item) {
        commit("EDIT_ITEM", item);
        return;
    },
    updateItem({ commit }, item) {
        return new Promise((resolve, reject) => {
            axios
                .post(`/api/subscription-management/update/${item.id}`, item)
                .then(response => {
                    if (response.data.success) {
                        commit("UPDATE_ITEM", response.data.success);
                        resolve(response);
                    }

                    reject({ message: response.data.error });
                })
                .catch(error => {
                    reject(error);
                });
        });
    },
    restoreItem({ commit }, id) {
        return new Promise((resolve, reject) => {
            axios
                .put(`/api/subscription-management/restore/${id}`)
                .then(response => {
                    if (response.data.success) {
                        commit("UPDATE_ITEM", response.data.success);
                        resolve(response);
                    }

                    reject({ message: response.data.error });
                })
                .catch(error => {
                    reject(error);
                });
        });
    },
    removeItem({ commit }, id) {
        return new Promise((resolve, reject) => {
            axios
                .delete(`/api/subscription-management/destroy/${id}`)
                .then(response => {
                    if (response.data.success) {
                        commit("UPDATE_ITEM", response.data.success);
                        resolve(response);
                    }

                    reject({ message: response.data.error });
                })
                .catch(error => {
                    reject(error);
                });
        });
    },
    forceRemoveItem({ commit }, id) {
        return new Promise((resolve, reject) => {
            axios
                .delete(`/api/subscription-management/forceDelete/${id}`)
                .then(response => {
                    if (response.data.success) {
                        commit("REMOVE_ITEM", id);
                        resolve(response);
                    }

                    reject({ message: response.data.error });
                })
                .catch(error => {
                    reject(error);
                });
        });
    }
};
