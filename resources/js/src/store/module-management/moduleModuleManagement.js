import { apiRequest } from "@/http/requests";
import { crud } from "../utils";

const slug = 'module-management';
const model = 'module';
const model_plurial = 'modules';

const { state, getters, actions, mutations } = crud(slug, model, model_plurial);

state.data_types = [];

getters.getDataTypes = state => JSON.parse(JSON.stringify(state.data_types || []));

actions.fetchDataTypes = ({ commit }) => {
    return apiRequest(`${slug}/data-types`, 'get', (payload) => commit('SET_DATA_TYPES', payload));
}
actions.testConnection = ({ commit }, payload) => {
    return apiRequest(`${slug}/test`, 'post', null, payload);
}
actions.updateConnection = ({ commit }, payload) => {
    return apiRequest(`${slug}/update-connection/${payload.id}`, 'put', (payload) => commit('ADD_OR_UPDATE_ITEM', payload), payload)
}
actions.updateDataTypes = ({ commit }, payload) => {
    return apiRequest(`${slug}/update-data-types/${payload.id}`, 'put', (payload) => commit('ADD_OR_UPDATE_ITEM', payload), payload)
}
actions.sync = ({ commit }, id) => {
    return apiRequest(`${slug}/sync/${id}`,
        'get',
        (payload) => commit('SET_MODULE', payload, { root: true })
    );
}

mutations.SET_DATA_TYPES = (state, items) => state["data_types"] = items;

export default {
    isRegistered: false,
    namespaced: true,
    state,
    mutations,
    actions,
    getters
};
