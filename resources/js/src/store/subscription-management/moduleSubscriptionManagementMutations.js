/*=========================================================================================
  File Name: moduleCalendarMutations.js
  Description: Calendar Module Mutations
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
==========================================================================================*/

var slug = "subscriptions";
var slug_singular = "subscription";

export default {
    SET_ITEMS(state, items) {
        state[slug] = items;
    },
    SET_PACKAGES(state, items) {
        state["packages"] = items;
    },
    ADD_ITEM(state, item) {
        state[slug].unshift(item);
    },
    EDIT_ITEM(state, item) {
        state[slug_singular] = item;
    },
    UPDATE_ITEM(state, item) {
        const index = state[slug].findIndex(r => r.id === item.id);
        state[slug].splice(index, 1, item);
    },
    REMOVE_ITEM(state, itemId) {
        const index = state[slug].findIndex(u => u.id === itemId);
        state[slug].splice(index, 1);
    }
};
