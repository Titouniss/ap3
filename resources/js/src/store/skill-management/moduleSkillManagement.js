import { crud } from "../utils";

const slug = 'skill-management';
const model = 'skill';
const model_plurial = 'skills';

const { state, getters, actions, mutations } = crud(slug, model, model_plurial);

getters.getItemsByCompany = state => id => JSON.parse(JSON.stringify((state[model_plurial] || []).filter((skill) => skill.company_id === id)));

export default {
    isRegistered: false,
    namespaced: true,
    state,
    mutations,
    actions,
    getters
}

