import crud from "../utils/crud";

const slug = 'range-management';
const model = 'range';
const model_plurial = 'ranges';

const { state, getters, actions, mutations } = crud(slug, model, model_plurial);

getters.getItemsForCompany = state => id => state.ranges.filter(range => range.company_id === id);

export default {
    isRegistered: false,
    namespaced: true,
    state,
    mutations,
    actions,
    getters
}

