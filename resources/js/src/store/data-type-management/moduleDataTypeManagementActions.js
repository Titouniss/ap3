import axios from "@/axios.js";

export default {
    fetchItems({ commit }) {
        return new Promise((resolve, reject) => {
            axios
                .get("/api/data-type-management/index")
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
    }
};
