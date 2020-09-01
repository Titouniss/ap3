export default {
    ADD_ITEM(state, item) {

        state.hours.unshift(item)

        let title = item.description ? item.project.name.toUpperCase() + ' - ' + item.description : item.project.name.toUpperCase()

        let itemForCalendar = {
            id: item.id,
            title: title,
            start: item.start_at,
            end: item.end_at,
            description: item.description,
            user_id: item.user_id,
            project_id: item.project_id,
            color: item.project.color,
        };
        state.hoursCalendar.unshift(itemForCalendar)
    },
    EDIT_ITEM(state, item) {
        state.hour = item
    },
    SET_HOURS(state, hours) {
        state.hours = hours

        let hoursForCalendar = []
        if (hours) {
            hours.forEach(t => {
                hoursForCalendar.push({
                    id: t.id,
                    title: t.description !== null ? t.project.name.toUpperCase() + ' - ' + t.description : t.project.name.toUpperCase(),
                    start: t.start_at,
                    end: t.end_at,
                    description: t.description,
                    user_id: t.user_id,
                    project_id: t.project_id,
                    color: t.project.color,
                });
            });
        }
        state.hoursCalendar = hoursForCalendar
    },
    UPDATE_ITEM(state, item) {

        const index = state.hours.findIndex((r) => r.id === item.id)
        Object.assign(state.hours[index], item)

        const index2 = state.hoursCalendar.findIndex((r) => r.id === item.id)
        let itemCalendar = {
            id: item.id,
            title: item.description !== null ? item.project.name.toUpperCase() + ' - ' + item.description : item.project.name.toUpperCase(),
            start: item.start_at,
            end: item.end_at,
            description: item.description,
            user_id: item.user_id,
            project_id: item.project_id,
            color: item.project.color,
        }

        Object.assign(state.hoursCalendar[index2], itemCalendar)
    },
    REMOVE_RECORD(state, itemId) {
        const index = state.hours.findIndex((u) => u.id === itemId)
        state.hours.splice(index, 1)

        const index2 = state.hoursCalendar.findIndex((u) => u.id === itemId)
        state.hoursCalendar.splice(index2, 1)
    }
}
