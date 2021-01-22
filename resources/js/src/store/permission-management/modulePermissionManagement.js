import { crud } from "../utils";

const slug = 'permission-management';
const model = 'permission';
const model_plurial = 'permissions';

const { state, getters, actions, mutations } = crud(slug, model, model_plurial);

export default {
    isRegistered: false,
    namespaced: true,
    state,
    mutations,
    actions,
    getters
}

