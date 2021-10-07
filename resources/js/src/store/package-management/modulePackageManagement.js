import { crud } from "../utils";

const slug = "package-management";
const model = "package";
const model_plurial = "packages";

const { state, getters, actions, mutations } = crud(slug, model, model_plurial);

export default {
    isRegistered: false,
    namespaced: true,
    state,
    mutations,
    actions,
    getters
};
