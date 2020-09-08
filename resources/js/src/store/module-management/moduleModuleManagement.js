/*=========================================================================================
  File Name: 
  Description: 
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/

import state from "./moduleModuleManagementState.js";
import mutations from "./moduleModuleManagementMutations.js";
import actions from "./moduleModuleManagementActions.js";
import getters from "./moduleModuleManagementGetters.js";

export default {
    isRegistered: false,
    namespaced: true,
    state,
    mutations,
    actions,
    getters
};
