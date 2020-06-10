import state from './moduleHoursManagementState.js'
import mutations from './moduleHoursManagementMutations.js'
import actions from './moduleHoursManagementActions.js'
import getters from './moduleHoursManagementGetters.js'

export default {
    isRegistered: false,
    namespaced: true,
    state,
    mutations,
    actions,
    getters
}

