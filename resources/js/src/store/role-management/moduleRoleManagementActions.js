import axios from "@/axios.js";

export default {
    addItem({ commit }, item) {
        return new Promise((resolve, reject) => {
            axios
                .post("/api/role-management/store", item)
                .then(response => {
                    commit(
                        "ADD_ITEM",
                        Object.assign(item, { id: response.data.id })
                    );
                    resolve(response);
                })
                .catch(error => {
                    console.log(error);
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
                .get("/api/role-management/index")
                .then(response => {
                    commit("SET_ROLES", response.data.success);
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
                .get(`/api/role-management/show/${id}`)
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
                .post(`/api/role-management/update/${payload.id}`, payload)
                .then(response => {
                    commit("UPDATE_ITEM", response.data.success);
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
                .put(`/api/role-management/restore/${id}`)
                .then(response => {
                    if (response.data.success) {
                        commit("UPDATE_ITEM", response.data.success);
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
                .delete(`/api/role-management/destroy/${id}`)
                .then(response => {
                    if (response.data.success) {
                        commit("UPDATE_ITEM", response.data.success);
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
    forceRemoveItem({ commit }, id) {
        return new Promise((resolve, reject) => {
            axios
                .delete(`/api/role-management/forceDelete/${id}`)
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
    }
};
