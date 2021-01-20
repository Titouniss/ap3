import crud from "../utils/crud";

const slug = 'skill-management';
const model = 'skill';
const model_plurial = 'skills';

const { state, getters, actions, mutations } = crud(slug, model, model_plurial);

export default {
    isRegistered: false,
    namespaced: true,
    state,
    mutations,
    actions,
    getters
}

