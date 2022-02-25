import { apiRequest } from "@/http/requests";
import { crud } from "../utils";

const slug = "todo-management";
const model = "todo";
const model_plurial = "todos";
const {actions, getters, state, mutations} = crud(slug, model, model_plurial);
//#endregion
export default {
    isRegistered: false,
    namespaced: true,
    state,
    mutations,
    actions,
    getters
};
