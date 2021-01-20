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

export default function (slug, model, model_plurial, sort_items = null) {
    return {
        apiRequest,
        state: {
            [model_plurial]: [],
            [model]: {}
        },

        getters: {
            getItems: state => (state[model_plurial] || []),
            getItem: state => id => (state[model_plurial] || []).find((item) => item.id === id)
        },

        actions: {
            fetchItems: ({ commit }, params = null) => {
                return apiRequest(`${slug}/index`, 'get', (payload) => commit('SET_ITEMS', payload), params);
            },
            fetchItem: ({ commit }, id) => {
                return apiRequest(`${slug}/show/${id}`, 'get', (payload) => commit('ADD_OR_UPDATE_ITEM', payload));
            },
            addItem: ({ commit }, payload) => {
                return apiRequest(`${slug}/store`, 'post', (payload) => commit('ADD_OR_UPDATE_ITEM', payload), payload);
            },
            editItem: ({ commit }, payload) => {
                const item = JSON.parse(JSON.stringify(payload));
                commit("EDIT_ITEM", item);
                return item;
            },
            updateItem: ({ commit }, payload) => {
                return apiRequest(`${slug}/update/${payload.id}`, 'put', (payload) => commit('ADD_OR_UPDATE_ITEM', payload), payload)
            },
            restoreItem: ({ commit }, id) => {
                return apiRequest(`${slug}/restore/${id}`, 'put', (payload) => commit('ADD_OR_UPDATE_ITEM', payload))
            },
            removeItem: ({ commit }, id) => {
                return apiRequest(`${slug}/destroy/${id}`, 'delete', (payload) => {
                    if (typeof payload === "boolean" && payload) {
                        commit('REMOVE_ITEM', id)
                    } else {
                        commit('ADD_OR_UPDATE_ITEM', payload)
                    }
                })
            },
            forceRemoveItem: ({ commit }, id) => {
                return apiRequest(`${slug}/forceDelete/${id}`, 'delete', (payload) => commit('REMOVE_ITEM', id))
            }
        },

        mutations: {
            SET_ITEMS: (state, items) => state[model_plurial] = items,
            ADD_OR_UPDATE_ITEM: (state, item) => {
                const index = state[model_plurial].findIndex((i) => i.id === item.id);
                if (index === -1) {
                    state[model_plurial].unshift(item)
                } else {
                    state[model_plurial].splice(index, 1, item)
                }
                if (sort_items) {
                    state[model_plurial].sort(sort_items)
                }
            },
            EDIT_ITEM: (state, item) => state[model] = item,
            REMOVE_ITEM: (state, id) => {
                const index = state[model_plurial].findIndex((i) => i.id === id);
                if (index > -1) {
                    state[model_plurial].splice(index, 1)
                }
            },
            EMPTY_ITEMS(state) {
                state[slug] = [];
            }
        }

    }


}
