import crud from "../utils/crud";

const slug = 'company-management';
const model = 'company';
const model_plurial = 'companies';

const { state, getters, actions, mutations } = crud(slug, model, model_plurial);

export default {
    isRegistered: false,
    namespaced: true,
    state,
    mutations,
    actions,
    getters
}

