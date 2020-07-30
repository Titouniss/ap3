import axios from "@/axios.js";

export default {
    addItem({ commit }, item) {
        return new Promise((resolve, reject) => {
            axios
                .post("/api/user-management/store", item)
                .then(response => {
                    if (response.data.success) {
                        commit(
                            "ADD_ITEM",
                            Object.assign(item, { id: response.data.success })
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
    fetchItems({ commit }) {
        return new Promise((resolve, reject) => {
            axios
                .get("/api/user-management/index")
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
                .get(`/api/user-management/show/${id}`)
                .then(response => {
                    resolve(response);
                })
                .catch(error => {
                    reject(error);
                });
        });
    },
    updateItem({ commit }, payload) {
        return new Promise((resolve, reject) => {
            axios
                .post(`/api/user-management/update/${payload.id}`, payload)
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
    updateAccountItem({ commit }, item) {
        return new Promise((resolve, reject) => {
            axios
                .post(`/api/user-management/updateAccount/${item.id}`, item)
                .then(response => {
                    commit("UPDATE_USER_INFO", response.data.success, {
                        root: true
                    });
                    resolve(response);
                })
                .catch(error => {
                    reject(error);
                });
        });
    },
    updateInformationItem({ commit }, item) {
        return new Promise((resolve, reject) => {
            axios
                .post(`/api/user-management/updateInformation/${item.id}`, item)
                .then(response => {
                    console.log(["response", response]);
                    commit("UPDATE_USER_INFO", response.data.success, {
                        root: true
                    });
                    resolve(response);
                })
                .catch(error => {
                    reject(error);
                });
        });
    },
    updatePassword({ commit }, payload) {
        return new Promise((resolve, reject) => {
            axios
                .post(
                    `/api/user-management/updatePassword/${payload.id_user}`,
                    payload
                )
                .then(response => {
                    resolve(response);
                })
                .catch(error => {
                    reject(error);
                });
        });
    },
    updateWorkHoursItem({ commit }, item) {
        return new Promise((resolve, reject) => {
            axios
                .post(`/api/user-management/updateWorkHours/${item.id}`, item)
                .then(response => {
                    commit("UPDATE_USER_INFO", response.data.success, {
                        root: true
                    });
                    resolve(response);
                })
                .catch(error => {
                    reject(error);
                });
        });
    },
    restoreItem({ commit }, id) {
        return new Promise((resolve, reject) => {
            axios
                .put(`/api/user-management/restore/${id}`)
                .then(response => {
                    commit("UPDATE_ITEM", response.data.success);
                    resolve(response);
                })
                .catch(error => {
                    reject(error);
                });
        });
    },
    removeItem({ commit }, id) {
        return new Promise((resolve, reject) => {
            axios
                .delete(`/api/user-management/destroy/${id}`)
                .then(response => {
                    commit("UPDATE_ITEM", response.data.success);
                    resolve(response);
                })
                .catch(error => {
                    reject(error);
                });
        });
    },
    forceRemoveItem({ commit }, id) {
        return new Promise((resolve, reject) => {
            axios
                .delete(`/api/user-management/forceDelete/${id}`)
                .then(response => {
                    commit("REMOVE_RECORD", id);
                    resolve(response);
                })
                .catch(error => {
                    reject(error);
                });
        });
    }
};
