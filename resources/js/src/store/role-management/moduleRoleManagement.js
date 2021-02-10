import { crud } from "../utils";

const slug = "role-management";
const model = "role";
const model_plurial = "roles";

const { state, getters, actions, mutations } = crud(slug, model, model_plurial);

getters.getItemsByCompany = currentState => id =>
    currentState.roles.filter(
        r =>
            (r.is_public && r.company_id === null) ||
            (id !== null && r.company_id === id)
    );

export default {
    isRegistered: false,
    namespaced: true,
    state,
    mutations,
    actions,
    getters
};
