import { crud } from "../utils";

const slug = 'workarea-management';
const model = 'workarea';
const model_plurial = 'workareas';

const { state, getters, actions, mutations } = crud(slug, model, model_plurial);

export default {
    isRegistered: false,
    namespaced: true,
    state,
    mutations,
    actions,
    getters
}

