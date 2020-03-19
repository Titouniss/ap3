/*=========================================================================================
  Description: Module Getters
==========================================================================================*/

export default {
  getItem: state => id => state.permissions.find((item) => item.id === id),
  getItems: state => state.permissions
}
