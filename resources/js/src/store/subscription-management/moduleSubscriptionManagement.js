import { crud } from "../utils";

const slug = "subscription-management";
const model = "subscription";
const model_plurial = "subscriptions";

const { state, getters, actions, mutations } = crud(slug, model, model_plurial);

export default {
    isRegistered: false,
    namespaced: true,
    state,
    mutations,
    actions,
    getters
};
