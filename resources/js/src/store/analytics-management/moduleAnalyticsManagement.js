import { crud } from "../utils";

const slug = "analytic-management";
const model = "analytic";
const model_plurial = "analytics";

const { state, getters, actions, mutations } = crud(slug, model, model_plurial);

export default {
    isRegistered: false,
    namespaced: true,
    state,
    mutations,
    actions,
    getters
};
