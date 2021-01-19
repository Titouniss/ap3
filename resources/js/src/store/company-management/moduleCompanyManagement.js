import crud from "../utils/crud";

const { state, getters, actions, mutations } = crud('company-management', 'company', 'companies');

export default {
    isRegistered: false,
    namespaced: true,
    state,
    mutations,
    actions,
    getters
}

