import { crud } from "../utils";

const slug = "unavailability-management";
const model = "unavailability";
const model_plurial = "unavailabilities";

const { state, getters, actions, mutations } = crud(slug, model, model_plurial);

export default {
    isRegistered: false,
    namespaced: true,
    state,
    mutations,
    actions,
    getters
};
