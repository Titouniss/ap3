/*=========================================================================================
  Description: Module Mutations
==========================================================================================*/

export default {
  ADD_ITEM (state, item) {
    state.permissions.unshift(item)
  },
  EDIT_ITEM (state, item) {
    state.permission = item
  },
  SET_ITEMS (state, permissions) {
    state.permissions = permissions
  },
  UPDATE_ITEM (state, item) {
    const index = state.permissions.findIndex((r) => r.id === item.id)    
    Object.assign(state.permissions[index], item)
  },
  REMOVE_RECORD (state, itemId) {
    const index = state.permissions.findIndex((u) => u.id === itemId)
    state.permissions.splice(index, 1)
  }
}
