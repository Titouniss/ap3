import { crud } from "../utils";

const slug = "hours-management";
const model = "hour";
const model_plurial = "hours";

const { state, getters, actions, mutations } = crud(slug, model, model_plurial);

state.hoursCalendar = [];

const gettersCopy = Object.assign({}, getters);

getters.getCalendarItems = currentState => {
    return JSON.parse(JSON.stringify(currentState.hoursCalendar || []));
};
getters.getItem = currentState => id => {
    const item = gettersCopy.getItem(currentState)(id);
    if (item && Object.keys(item).length !== 0) {
        let arrayStart = item.start_at.split(" ");
        let arrayEnd = item.end_at.split(" ");

        item.date = arrayStart[0];
        item.startHour = arrayStart[1];
        item.endHour = arrayEnd[1];
    }
    return item;
};
getters.getSelectedItem = currentState => {
    const item = gettersCopy.getSelectedItem(currentState);
    if (item && Object.keys(item).length !== 0) {
        let arrayStart = (item.start || item.start_at).split(" ");
        let arrayEnd = (item.end || item.end_at).split(" ");

        item.date = arrayStart[0];
        item.startHour = arrayStart[1];
        item.endHour = arrayEnd[1];
    }
    return item;
};

const mutationsCopy = Object.assign({}, mutations);

mutations.SET_ITEMS = (currentState, items) => {
    mutationsCopy.SET_ITEMS(currentState, items);
    const calendarItems = [];
    if (items) {
        items.forEach(t => {
            calendarItems.push({
                id: t.id,
                title:
                    t.description !== null
                        ? t.project.name.toUpperCase() + " - " + t.description
                        : t.project.name.toUpperCase(),
                start: t.start_at,
                end: t.end_at,
                description: t.description,
                user_id: t.user_id,
                project_id: t.project_id,
                color: t.project.color
            });
        });
    }
    currentState.hoursCalendar = calendarItems;
};
mutations.ADD_OR_UPDATE_ITEMS = (currentState, items) => {
    mutationsCopy.ADD_OR_UPDATE_ITEMS(currentState, items);
    (Array.isArray(items) ? items : [items]).forEach(new_item => {
        const index = currentState.hoursCalendar.findIndex(
            item => item.id === new_item.id
        );
        const calendarItem = {
            id: new_item.id,
            title:
                new_item.project.name.toUpperCase() +
                (new_item.description ? " - " + new_item.description : ""),
            start: new_item.start_at,
            end: new_item.end_at,
            description: new_item.description,
            user_id: new_item.user_id,
            project_id: new_item.project_id,
            color: new_item.project.color
        };

        if (index === -1) {
            currentState.hoursCalendar.unshift(calendarItem);
        } else {
            currentState.hoursCalendar.splice(index, 1, calendarItem);
        }
    });
};
mutations.REMOVE_ITEMS = (currentState, ids) => {
    mutationsCopy.REMOVE_ITEMS(currentState, ids);
    (Array.isArray(ids) ? ids : [ids]).forEach(id => {
        const index = currentState.hoursCalendar.findIndex(
            item => item.id === id
        );
        if (index > -1) {
            currentState.hoursCalendar.splice(index, 1);
        }
    });
};
mutations.EMPTY_ITEMS = currentState => {
    mutations.EMPTY_ITEMS(currentState);
    currentState.hoursCalendar = [];
};

export default {
    isRegistered: false,
    namespaced: true,
    state,
    mutations,
    actions,
    getters
};
