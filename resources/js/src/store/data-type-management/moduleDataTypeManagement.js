/*=========================================================================================
  File Name: 
  Description: 
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/

import state from "./moduleDataTypeManagementState.js";
import mutations from "./moduleDataTypeManagementMutations.js";
import actions from "./moduleDataTypeManagementActions.js";
import getters from "./moduleDataTypeManagementGetters.js";

export default {
    isRegistered: false,
    namespaced: true,
    state,
    mutations,
    actions,
    getters
};
