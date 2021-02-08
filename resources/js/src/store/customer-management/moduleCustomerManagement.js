import { crud } from "../utils";

const slug = 'customer-management';
const model = 'customer';
const model_plurial = 'customers';

const { state, getters, actions, mutations } = crud(slug, model, model_plurial);

export default {
    isRegistered: false,
    namespaced: true,
    state,
    mutations,
    actions,
    getters
}

