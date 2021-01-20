import crud from "../utils/crud";

const slug = 'subscription-management';
const model = 'subscription';
const model_plurial = 'subscriptions';

const { apiRequest, state, getters, actions, mutations } = crud(slug, model, model_plurial);

state.packages = [];

getters.getPackages = state => JSON.parse(JSON.stringify(state.packages || []));

actions.fetchPackages = ({ commit }) => {
    return apiRequest(`${slug}/packages`, 'get', (payload) => commit('SET_PACKAGES', payload));
};

mutations.SET_PACKAGES = (state, items) => state["packages"] = items;

export default {
    isRegistered: false,
    namespaced: true,
    state,
    mutations,
    actions,
    getters
};
