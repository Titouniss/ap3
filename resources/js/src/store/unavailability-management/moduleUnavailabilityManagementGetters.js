export default {
    getItem: state => id => state.unavailabilities.find((u) => u.id === id),
}
