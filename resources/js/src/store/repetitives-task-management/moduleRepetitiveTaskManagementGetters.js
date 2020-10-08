export default {
    getItem: state => id => {
        return { ...state.repetitivesTasks.find(item => item.id === id) };
    }
};
