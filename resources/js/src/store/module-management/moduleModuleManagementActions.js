import axios from "@/axios.js";

export default {
    addItem({ commit }, item) {
        return new Promise((resolve, reject) => {
            axios
                .post("/api/module-management/store", item)
                .then(response => {
                    if (response.data.success) {
                        commit(
                            "ADD_ITEM",
                            Object.assign(item, response.data.success)
                        );
                        resolve(response);
                    } else {
                        reject({ message: response.data.error });
                    }
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
                .post(`/api/module-management/update/${item.id}`, item)
                .then(response => {
                    if (response.data.success) {
                        commit(
                            "UPDATE_ITEM",
                            Object.assign(item, response.data.success)
                        );
                        resolve(response);
                    } else {
                        reject({ message: response.data.error });
                    }
                })
                .catch(error => {
                    reject(error);
                });
        });
    },
    fetchItems({ commit }) {
        return new Promise((resolve, reject) => {
            axios
                .get("/api/module-management/index")
                .then(response => {
                    if (response.data.success) {
                        commit("SET_ITEMS", response.data.success);
                        resolve(response);
                    } else {
                        reject({ message: response.data.error });
                    }
                })
                .catch(error => {
                    reject(error);
                });
        });
    },
    fetchItem(context, id) {
        return new Promise((resolve, reject) => {
            axios
                .get(`/api/module-management/show/${id}`)
                .then(response => {
                    if (response.data.success) {
                        resolve(response);
                    } else {
                        reject({ message: response.data.error });
                    }
                })
                .catch(error => {
                    reject(error);
                });
        });
    },
    removeItem({ commit }, id) {
        return new Promise((resolve, reject) => {
            axios
                .delete(`/api/module-management/destroy/${id}`)
                .then(response => {
                    if (response.data.success) {
                        commit("REMOVE_ITEM", id);
                        resolve(response);
                    } else {
                        reject({ message: response.data.error });
                    }
                })
                .catch(error => {
                    reject(error);
                });
        });
    },
    testConnection({ commit }, payload) {
        return new Promise((resolve, reject) => {
            axios
                .post("/api/module-management/test-connection", payload)
                .then(response => {
                    if (response.data.success) {
                        resolve(response);
                    } else {
                        reject({ message: response.data.error });
                    }
                })
                .catch(error => {
                    reject(error);
                });
        });
    },
    updateModule({ commit }, payload) {
        return new Promise((resolve, reject) => {
            axios
                .post(
                    `/api/module-management/module-update/${payload.id}`,
                    payload
                )
                .then(response => {
                    if (response.data.success) {
                        resolve(response);
                    } else {
                        reject({ message: response.data.error });
                    }
                })
                .catch(error => {
                    reject(error);
                });
        });
    },
    updateModuleDataTypes({ commit }, payload) {
        return new Promise((resolve, reject) => {
            axios
                .post(
                    `/api/module-management/module-data-types-update/${payload.id}`,
                    payload
                )
                .then(response => {
                    if (response.data.success) {
                        resolve(response);
                    } else {
                        reject({ message: response.data.error });
                    }
                })
                .catch(error => {
                    reject(error);
                });
        });
    }
};
