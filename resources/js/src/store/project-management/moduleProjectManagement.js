import { apiRequest } from "@/http/requests";
import { crud } from "../utils";

const slug = 'project-management';
const model = 'project';
const model_plurial = 'projects';

const { state, getters, actions, mutations } = crud(slug, model, model_plurial);

actions.start = ({ commit }, item) => {
    return apiRequest(`${slug}/start/${item.id}`, 'post', null, item);
}

export default {
    isRegistered: false,
    namespaced: true,
    state,
    mutations,
    actions,
    getters
}

