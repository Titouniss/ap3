import { apiRequest } from "@/http/requests";
import { crud } from "../utils";

const slug = "tag-management";
const model = "tag";
const model_plurial = "tags";
const {actions, getters, state, mutations} = crud(slug, model, model_plurial);

export default {
    isRegistered: false,
    namespaced: true,
    state,
    mutations,
    actions,
    getters
};
