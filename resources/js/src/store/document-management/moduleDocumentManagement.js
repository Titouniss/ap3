import { apiPostFormData } from "@/http/requests";
import { crud } from "../utils";

const slug = 'document-management';
const model = 'document';
const model_plurial = 'documents';

const { actions: { addItem, removeItems }, mutations } = crud(slug, model, model_plurial);

const state = {};

const getters = {};

const actions = {
    addItem,
    removeItems,
    upload: ({ commit }, item) => {
        return apiPostFormData(`${slug}/upload/${item.token}`, item.file, (payload) => commit('ADD_OR_UPDATE_ITEMS', payload))
    }
}

export default {
    isRegistered: false,
    namespaced: true,
    state,
    mutations,
    actions,
    getters
};
