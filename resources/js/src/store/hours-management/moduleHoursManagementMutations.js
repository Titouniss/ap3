export default {
    ADD_ITEM(state, item) {
        state.allHours.unshift(item)
    },
    EDIT_ITEM(state, item) {
        state.hours = item
    },
    SET_HOURS(state, allHours) {
        state.allHours = allHours
    },
    UPDATE_ITEM(state, item) {
        const index = state.allHours.findIndex((r) => r.id === item.id)
        Object.assign(state.allHours[index], item)
    },
    REMOVE_RECORD(state, itemId) {
        const index = state.allHours.findIndex((u) => u.id === itemId)
        state.allHours.splice(index, 1)
    }
}
