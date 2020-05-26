export default {
    getItem: state => id => state.allHours.find((item) => item.id === id),
    getItems: state => state.allHours,
}
