import axios from "@/axios.js";

export default {
    addItem({ commit }, item) {
        console.log(item);
        return new Promise((resolve, reject) => {
            axios
                .post("/api/unavailability-management/store", item)
                .then(response => {
                    if (response.data && response.data.success) {
                        commit(
                            "ADD_ITEM",
                            Object.assign(item, {
                                id: response.data.success.id
                            })
                        );
                        resolve(response);
                    }
                    reject({ message: response.data.error });
                })
                .catch(error => {
                    console.log(error.response);
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
                .get("/api/unavailability-management/index")
                .then(response => {
                    commit("SET_ITEMS", response.data.success);
                    resolve(response);
                })
                .catch(error => {
                    reject(error);
                });
        });
    },
    updateItem({ commit }, item) {
        return new Promise((resolve, reject) => {
            axios
                .post(`/api/unavailability-management/update/${item.id}`, item)
                .then(response => {
                    if (response.data.success) {
                        commit("UPDATE_ITEM", response.data.success);
                        resolve(response);
                    } else {
                        reject(response);
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
                .delete(`/api/unavailability-management/destroy/${id}`)
                .then(response => {
                    if (response.data.success) {
                        commit("REMOVE_ITEM", id);
                        resolve(response);
                    } else {
                        reject(response);
                    }
                })
                .catch(error => {
                    reject(error);
                });
        });
    }
};
