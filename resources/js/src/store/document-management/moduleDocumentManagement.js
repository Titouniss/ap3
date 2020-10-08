/*=========================================================================================
  File Name: 
  Description: 
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/

import state from "./moduleDocumentManagementState.js";
import mutations from "./moduleDocumentManagementMutations.js";
import actions from "./moduleDocumentManagementActions.js";
import getters from "./moduleDocumentManagementGetters.js";

export default {
    isRegistered: false,
    namespaced: true,
    state,
    mutations,
    actions,
    getters
};
