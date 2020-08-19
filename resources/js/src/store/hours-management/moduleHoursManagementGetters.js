export default {
    getItem: state => id => {
        let data = state.hours.find((item) => item.id === id)
        if(data){
            let arrayStart = data.start_at.split(" ")
            let arrayEnd = data.end_at.split(" ")

            data.date = arrayStart[0]
            data.startHour = arrayStart[1]
            data.endHour = arrayEnd[1]
        }
        return data;
    },
    getItems: state => state.hours,
}
