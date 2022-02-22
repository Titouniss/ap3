import { crud } from "../utils";

const slug = "bug-management";
const model = "bug";
const model_plurial = "bugs";

const { state, getters, actions, mutations } = crud(slug, model, model_plurial);

export default {
    isRegistered: false,
    namespaced: true,
    state,
    mutations,
    actions,
    getters
};
