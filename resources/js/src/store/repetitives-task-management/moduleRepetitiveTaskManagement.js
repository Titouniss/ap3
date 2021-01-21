import crud from "../utils/crud";

const slug = 'repetitive-task-management';
const model = 'repetitive_task'
const model_plurial = 'repetitive_tasks'

const { apiRequest, state, getters, mutations } = crud(slug, model, model_plurial, (a, b) => a.order - b.order);

const actions = {
    fetchItemsByRange({ commit }, id) {
        return apiRequest(`${slug}/range/${id}`, 'get', (payload) => {
            const items = payload.map(item => {
                const task = item;
                if (task.skills.length > 0) {
                    task.skills = task.skills.map(skill => skill.id);
                }
                return task;
            })
            commit('SET_ITEMS', items)
        });
    },
    addItem({ commit }, payload) {
        payload.id = "TEMPORARY_ID_" + Math.floor(100000 + Math.random() * 900000);
        return commit('ADD_OR_UPDATE_ITEMS', payload)
    },
    editItem({ commit }, payload) {
        return commit('EDIT_ITEM', payload)
    },
    updateItem({ commit }, payload) {
        commit('ADD_OR_UPDATE_ITEMS', payload)
    },
    removeItem({ commit }, id) {
        commit('REMOVE_ITEMS', id)
    },
    emptyItems({ commit }) {
        commit('EMPTY_ITEMS')
    },
}

export default {
    isRegistered: false,
    namespaced: true,
    state,
    mutations,
    actions,
    getters
}

