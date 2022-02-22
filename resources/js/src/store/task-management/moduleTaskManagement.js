import { apiRequest } from "@/http/requests";
import { crud } from "../utils";

const slug = "task-management";
const model = "task";
const model_plurial = "tasks";

const { state, getters, actions, mutations } = crud(slug, model, model_plurial);
state.task = {};
getters.getTask = state => JSON.parse(JSON.stringify(state.task || {}));

actions.getTasksById = ({ commit }, params = null) => {
    return apiRequest(
        `${slug}/task/${params}`,
        "get",
        payload => commit("SET_TASK", payload),
        params
    );
}
mutations.SET_TASK = (state, items) => (state.task = items);


//#region Comments

state.comments = [];

getters.getComments = state => JSON.parse(JSON.stringify(state.comments || []));

actions.fetchComments = ({ commit }, params = null) => {
    return apiRequest(
        `${slug}/comments`,
        "get",
        payload => commit("SET_COMMENTS", payload),
        params
    );
};

mutations.SET_COMMENTS = (state, items) => (state.comments = items);

//#endregion

const actionsCopy = Object.assign({}, actions);

actions.removeItems = ({ commit }, ids) => {
    actionsCopy
        .removeItems({ commit }, ids)
        .then(() => commit("REMOVE_ITEMS", ids));
};

actions.forceRemoveItems = ({ commit }, ids) => {
    actionsCopy
        .forceRemoveItems({ commit }, ids)
        .then(() => commit("REMOVE_ITEMS", ids));
};

actions.addComment = ({ commit }, item) => {
    return apiRequest(
        `${slug}/store-comment/${item.id}`,
        "post",
        payload => commit("ADD_OR_UPDATE_ITEMS", payload),
        item
    );
};

actions.addItemRange = ({ commit }, item) => {
    return apiRequest(
        `project-management/store-range/${item.rangeId}`,
        "post",
        payload => commit("ADD_OR_UPDATE_ITEMS", payload),
        item
    );
};

actions.updateTaskPeriod = ({ commit }, item) => {
    return apiRequest(
        `project-management/updateTaskPeriod`,
        "get",
        payload => commit("ADD_OR_UPDATE_ITEMS", payload),
        item
    );
};

export default {
    isRegistered: false,
    namespaced: true,
    state,
    mutations,
    actions,
    getters
};
