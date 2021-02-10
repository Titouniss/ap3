import { crud } from "../utils";

const slug = "schedule-management";
const model = "schedule";
const model_plurial = "schedules";

const { state, getters, mutations } = crud(slug, model, model_plurial);

const actions = {
    addItem({ commit }, item) {
        commit("ADD_OR_UPDATE_ITEMS", item);
        return item;
    },
    addItems({ commit }, items) {
        commit("ADD_OR_UPDATE_ITEMS", items);
        return items;
    },
    editItem({ commit }, item) {
        commit("EDIT_ITEM", item);
        return item;
    },
    updateItem({ commit }, item) {
        commit("ADD_OR_UPDATE_ITEMS", item);
        return item;
    },
    removeItem({ commit }, id) {
        commit("REMOVE_ITEMS", id), commit("EDIT_ITEM", {});
        return true;
    }
};

export default {
    isRegistered: false,
    namespaced: true,
    state,
    mutations,
    actions,
    getters
};
