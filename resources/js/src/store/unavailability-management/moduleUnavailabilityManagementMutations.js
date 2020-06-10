export default {
    ADD_ITEM(state, item) {
        state.unavailabilities.unshift(item)
    },
    EDIT_ITEM(state, item) {
        state.unavailability = item
    },
    SET_ITEMS(state, items) {
        state.unavailabilities = items
    },
    UPDATE_ITEM(state, item) {
        const index = state.unavailabilities.findIndex((u) => u.id === item.id)
        Object.assign(state.unavailabilities[index], item)
    },
    REMOVE_ITEM(state, itemId) {
        const index = state.unavailabilities.findIndex((u) => u.id === itemId)
        state.unavailabilities.splice(index, 1)
    }
}
