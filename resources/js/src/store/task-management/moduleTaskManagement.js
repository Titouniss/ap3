import { apiRequest } from "@/http/requests";
import { crud } from "../utils";

const slug = 'task-management';
const model = 'task';
const model_plurial = 'tasks';

const { state, getters, actions, mutations } = crud(slug, model, model_plurial);

const actionsCopy = Object.assign({}, actions);

actions.removeItems = ({ commit }, ids) => {
    actionsCopy.removeItems({ commit }, ids).then(() => commit('REMOVE_ITEMS', ids));
}

actions.addComment = ({ commit }, item) => {
    return apiRequest(`${slug}/store-comment/${item.id}`, 'post', (payload) => commit('ADD_OR_UPDATE_ITEMS', payload), item);
}

actions.addItemRange = ({ commit }, item) => {
    return apiRequest(`project-management/store-range/${item.range_id}`, 'post', (payload) => commit('ADD_OR_UPDATE_ITEMS', payload), item);
}

export default {
    isRegistered: false,
    namespaced: true,
    state,
    mutations,
    actions,
    getters
}

