import axios from "@/axios.js";

const apiRequest = (url, method = 'get', onSuccess = null, payload = null) => {
    let localMethod = method;
    let localPayload = payload ? JSON.parse(JSON.stringify(payload)) : null;
    switch (localMethod) {
        case 'post':
        case 'put':
        case 'delete':
            break;
        default: // get
            localMethod = 'get';
            if (localPayload) {
                localPayload = { params: localPayload };
            }
            break;
    }

    return new Promise((resolve, reject) => {
        axios[method](`/api/${url}`, localPayload)
            .then(response => {
                console.log(response)
                if (response && response.data && response.data.success) {
                    if (onSuccess) {
                        onSuccess(JSON.parse(JSON.stringify(response.data.payload)));
                    }
                    resolve(response.data);
                } else {
                    reject(response.data);
                }
            })
            .catch(error => {
                console.log(error)
                reject(error);
            });
    });
}

const apiPostFormData = (url, payload, onSuccess = null) => {
    return new Promise((resolve, reject) => {
        axios.post(`/api/${url}`, payload)
            .then(response => {
                if (response && response.data && response.data.success) {
                    if (onSuccess) {
                        onSuccess(JSON.parse(JSON.stringify(response.data.payload)));
                    }
                    resolve(response.data);
                } else {
                    reject(response.data);
                }
            })
            .catch(error => {
                console.log(error)
                reject(error);
            });
    });
}

export default function (slug, model, model_plurial, sort_items = null) {
    return {
        apiRequest,
        apiPostFormData,

        state: {
            [model_plurial]: [],
            [model]: {}
        },

        getters: {
            getItems: state => JSON.parse(JSON.stringify(state[model_plurial] || [])),
            getItem: state => id => JSON.parse(JSON.stringify((state[model_plurial] || []).find((item) => item.id === id))),
            getSelectedItem: state => JSON.parse(JSON.stringify(state[model] || {})),
        },

        actions: {
            fetchItems: ({ commit }, params = null) => {
                return apiRequest(`${slug}/index`, 'get', (payload) => commit('SET_ITEMS', payload), params);
            },
            fetchItem: ({ commit }, id) => {
                return apiRequest(`${slug}/show/${id}`, 'get', (payload) => commit('ADD_OR_UPDATE_ITEMS', payload));
            },
            addItem: ({ commit }, item) => {
                return apiRequest(`${slug}/store`, 'post', (payload) => commit('ADD_OR_UPDATE_ITEMS', payload), item);
            },
            editItem: ({ commit }, item) => {
                const itemToEdit = JSON.parse(JSON.stringify(item));
                commit("EDIT_ITEM", itemToEdit);
                return itemToEdit;
            },
            updateItem: ({ commit }, item) => {
                return apiRequest(`${slug}/update/${item.id}`, 'put', (payload) => commit('ADD_OR_UPDATE_ITEMS', payload), item)
            },
            restoreItems: ({ commit }, ids) => {
                return apiRequest(`${slug}/restore`, 'put', (payload) => commit('ADD_OR_UPDATE_ITEMS', payload), { ids })
            },
            removeItems: ({ commit }, ids) => {
                return apiRequest(`${slug}/destroy`, 'put', (payload) => {
                    if (typeof payload === "boolean" && payload) {
                        commit('REMOVE_ITEMS', ids)
                    } else {
                        commit('ADD_OR_UPDATE_ITEMS', payload)
                    }
                }, { ids })
            },
            forceRemoveItems: ({ commit }, ids) => {
                return apiRequest(`${slug}/force-destroy`, 'put', (payload) => commit('REMOVE_ITEMS', ids), { ids })
            },
        },

        mutations: {
            SET_ITEMS: (state, items) => state[model_plurial] = items,
            ADD_OR_UPDATE_ITEMS: (state, items) => {
                (Array.isArray(items) ? items : [items]).forEach(new_item => {
                    const index = state[model_plurial].findIndex((item) => item.id === new_item.id);
                    if (index === -1) {
                        state[model_plurial].unshift(new_item)
                    } else {
                        state[model_plurial].splice(index, 1, new_item)
                    }
                });
                if (sort_items) {
                    state[model_plurial].sort(sort_items)
                }
            },
            EDIT_ITEM: (state, item) => state[model] = item,
            REMOVE_ITEMS: (state, ids) => {
                (Array.isArray(ids) ? ids : [ids]).forEach(id => {
                    const index = state[model_plurial].findIndex((item) => item.id === id);
                    if (index > -1) {
                        state[model_plurial].splice(index, 1)
                    }
                });
            },
            EMPTY_ITEMS(state) {
                state[slug] = [];
            }
        }

    }
}
