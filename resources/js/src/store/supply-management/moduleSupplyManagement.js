import { apiRequest } from "@/http/requests";
import { crud } from "../utils";

const slug = "supply-management";
const model = "supply";
const model_plurial = "supplies";
const {actions, getters, state, mutations} = crud(slug, model, model_plurial);



export default {
    isRegistered: false,
    namespaced: true,
    state,
    mutations,
    actions,
    getters
};
