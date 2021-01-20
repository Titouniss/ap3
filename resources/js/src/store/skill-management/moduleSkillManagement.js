import crud from "../utils/crud";

const slug = 'skill-management';
const model = 'skill';
const model_plurial = 'skills';

const { apiRequest, state, getters, actions, mutations } = crud(slug, model, model_plurial);

actions.fetchItemByTask = ({ commit }, id) => {
    return apiRequest(`${slug}/index/task/${id}`, 'get', (payload) => commit('SET_ITEMS', payload));
}

export default {
    isRegistered: false,
    namespaced: true,
    state,
    mutations,
    actions,
    getters
}

