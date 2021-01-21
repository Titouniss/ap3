import crud from "../utils/crud";

const slug = 'role-management';
const model = 'role';
const model_plurial = 'roles';

const { state, getters, actions, mutations } = crud(slug, model, model_plurial);

export default {
    isRegistered: false,
    namespaced: true,
    state,
    mutations,
    actions,
    getters
}

