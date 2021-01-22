import { crud } from "../utils";

const slug = 'range-management';
const model = 'range';
const model_plurial = 'ranges';

const { state, getters, actions, mutations } = crud(slug, model, model_plurial);

export default {
    isRegistered: false,
    namespaced: true,
    state,
    mutations,
    actions,
    getters
}

