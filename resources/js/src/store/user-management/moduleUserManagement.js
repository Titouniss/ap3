import { apiRequest } from "@/http/requests";
import { crud } from "../utils";

const slug = 'user-management';
const model = 'user';
const model_plurial = 'users';

const { state, getters, actions, mutations } = crud(slug, model, model_plurial);

actions.updateAccount = ({ commit }, item) => {
    return apiRequest(`${slug}/update-account`, 'put', (payload) => commit("UPDATE_USER_INFO", payload, {
        root: true
    }), item);
}
actions.updatePassword = ({ commit }, item) => {
    return apiRequest(`${slug}/update-password`, 'put', null, item);
}
actions.updateWorkHours = ({ commit }, item) => {
    return apiRequest(`${slug}/update/${item.id}/work-hours`, 'put', (payload) => commit("ADD_OR_UPDATE_ITEMS", payload), item);
}

export default {
    isRegistered: false,
    namespaced: true,
    state,
    mutations,
    actions,
    getters
}

