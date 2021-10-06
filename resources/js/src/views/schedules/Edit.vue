<template>
    <div>
        <router-link
            :to="'/schedules'"
            class="btnBack flex cursor-pointer text-inherit hover:text-primary pt-3 mb-3"
        >
            <feather-icon class="'h-5 w-5" icon="ArrowLeftIcon"></feather-icon>
            <span class="ml-2">Retour à la liste des plannings</span>
        </router-link>

        <div class="vx-card w-full p-6">
            <h2 class="mb-4 color-primary">{{ scheduleTitle }}</h2>
            <!-- <div>
        <button @click="toggleWeekends">toggle weekends</button>
        <button @click="gotoPast">go to a date in the past</button>
        (also, click a date/time to add an event)
      </div>-->
            <add-form
                v-if="project_data"
                :activeAddPrompt="this.activeAddPrompt"
                :handleClose="handleClose"
                :dateData="dateData"
                :project_data="project_data"
                :tasks_list="tasksEvent"
                :customTask="false"
                :type="this.$route.query.type"
                :idType="parseInt(this.$route.query.id, 10)"
                :hideProjectInput="
                    this.$route.query.type === 'projects' ? true : false
                "
                :hideUserInput="
                    this.$route.query.type === 'users' ? true : false
                "
            />
            <FullCalendar
                locale="fr"
                class="demo-app-calendar border-c"
                ref="fullCalendar"
                defaultView="timeGridWeek"
                :editable="true"
                :header="{
                    left: 'prev today next',
                    center: 'dayGridMonth, timeGridWeek, timeGridDay',
                    right: 'title'
                }"
                :views="{
                    timeGridWeek: {
                        titleFormat: '{dddd DD MMM}, [Semaine] w, YYYY'
                    }
                }"
                :buttonText="{
                    today: 'Aujourd\'hui',
                    month: 'Mois',
                    week: 'Semaine',
                    day: 'Jour',
                    list: 'Liste'
                }"
                :allDaySlot="false"
                :plugins="calendarPlugins"
                :weekends="calendarWeekends"
                :events="calendarEvents"
                :firstDay="1"
                :minTime="minTime"
                :maxTime="maxTime"
                contentHeight="auto"
                :weekNumbers="true"
                :businessHours="businessHours"
                @eventDrop="handleEventDrop"
                @dateClick="handleDateClick"
                @eventClick="handleEventClick"
                @eventResize="handleEventResize"
            />
            <EditFormTaskPeriod
                :itemId="itemIdToEdit"
                :taskId="taskIdItem"
                :start_at="start_atItem"
                :end_at="end_atItem"
                :tasks_list="tasksEvent"
                :unavailabilities_list="this.unavailableEvent"
                :type="this.$route.query.type"
                :idType="parseInt(this.$route.query.id, 10)"
                v-if="itemIdToEdit && authorizedTo('edit')"
            />
        </div>
    </div>
</template>

<script>
import vSelect from "vue-select";
import moment from "moment";

import FullCalendar from "@fullcalendar/vue";
import dayGridPlugin from "@fullcalendar/daygrid";
import timeGridPlugin from "@fullcalendar/timegrid";
import interactionPlugin from "@fullcalendar/interaction";
import momentPlugin from "@fullcalendar/moment";

// Store Module
import moduleScheduleManagement from "@/store/schedule-management/moduleScheduleManagement.js";
import moduleTaskManagement from "@/store/task-management/moduleTaskManagement.js";
import moduleSkillManagement from "@/store/skill-management/moduleSkillManagement.js";
import moduleUserManagement from "@/store/user-management/moduleUserManagement.js";
import moduleDocumentManagement from "@/store/document-management/moduleDocumentManagement.js";
import moduleProjectManagement from "@/store/project-management/moduleProjectManagement.js";
import moduleWorkareaManagement from "@/store/workarea-management/moduleWorkareaManagement.js";

// Component
import EditForm from "../tasks/EditForm.vue";
import EditFormTaskPeriod from "../tasks/EditFormTaskPeriod.vue";
import AddForm from "../tasks/AddForm.vue";

// must manually include stylesheets for each plugin
import "@fullcalendar/core/main.css";
import "@fullcalendar/daygrid/main.css";
import "@fullcalendar/timegrid/main.css";

var model = "schedule";
var modelPlurial = "schedules";
var modelTitle = "Plannings";

export default {
    components: {
        EditForm,
        EditFormTaskPeriod,
        AddForm,
        FullCalendar // make the <FullCalendar> tag available
    },
    data: function() {
        return {
            calendarPlugins: [
                // plugins must be defined in the JS
                dayGridPlugin,
                timeGridPlugin,
                interactionPlugin, // needed for dateClick
                momentPlugin
            ],
            activeAddPrompt: false,
            dateData: {},
            taskBundle: null,
            tasksPeriod: null,
            calendarWeekends: true,
            customButtons: {
                AddEventBtn: {
                    text: "custom!",
                    click: function() {
                        alert("clicked the custom button!");
                    }
                }
            },
            businessHours: false,
            minTime: "05:00",
            maxTime: "24:00",
            date: null
        };
    },
    computed: {
        itemIdToEdit() {
            return this.$store.state.taskManagement.task.period_id || 0;
        },
        taskIdItem() {
            return this.$store.state.taskManagement.task.id || 0;
        },
        start_atItem() {
            return this.$store.state.taskManagement.task.start || 0;
        },
        end_atItem() {
            return this.$store.state.taskManagement.task.end || 0;
        },
        calendarEvents() {
            // Get all task and parse to show
            var eventsParse = [];
            let minHour = null;
            let maxHour = null;
            if (this.$route.query.type === "projects") {
                if (this.tasksEvent !== []) {
                    var task;
                    this.tasksEvent.forEach(t => {
                        if (t.id == this.$route.query.task_id) {
                            task = t;
                        }
                    });
                    if (task != null) {
                        task.periods.forEach(p => {
                            let start_period_hour = moment(p.start_time).format(
                                "HH:mm"
                            );
                            let end_period_hour = moment(p.end_time).format(
                                "HH:mm"
                            );

                            minHour == null || start_period_hour < minHour
                                ? (minHour = start_period_hour)
                                : null;
                            maxHour == null || end_period_hour > maxHour
                                ? (maxHour = end_period_hour)
                                : null;

                            eventsParse.push({
                                id: task.id,
                                period_id: p.id,
                                title: task.name,
                                start: p.start_time,
                                estimated_time: task.estimated_time,
                                order: task.order,
                                description: task.description,
                                time_spent: task.time_spent,
                                workarea_id: task.workarea_id,
                                status: task.status,
                                end: p.end_time,
                                user_id: task.user_id,
                                project_id: task.project_id,
                                color: task.project.color
                            });
                        });
                    }
                }
                if (this.unavailableEvent != null) {
                    let workHours;
                    for (var i = 0; i < this.unavailableEvent.length; i++) {
                        if (this.unavailableEvent[i].lundi != null) {
                            workHours = this.$store.state.projectManagement
                                .projects[i];
                        }
                    }
                    for (var i = 0; i < this.unavailableEvent.length; i++) {
                        if (
                            this.unavailableEvent[i].date_end != null &&
                            this.unavailableEvent[i].date !=
                                this.unavailableEvent[i].date_end &&
                            workHours != null
                        ) {
                            let hoursDay = [];
                            let heureDebutMatin;
                            let heureFinMatin;
                            let heureDebutApresMidi;
                            let heureFinApresMidi;
                            if (
                                this.$store.state.projectManagement.projects !=
                                    null &&
                                this.$store.state.projectManagement
                                    .projects[0] != null
                            ) {
                                hoursDay.push(workHours["dimanche"]);
                                hoursDay.push(workHours["lundi"]);
                                hoursDay.push(workHours["mardi"]);
                                hoursDay.push(workHours["mercredi"]);
                                hoursDay.push(workHours["jeudi"]);
                                hoursDay.push(workHours["vendredi"]);
                                hoursDay.push(workHours["samedi"]);
                                if (
                                    hoursDay != null &&
                                    hoursDay.length > 0 &&
                                    hoursDay[0] != null
                                ) {
                                    for (var d = 0; d < hoursDay.length; d++) {
                                        //événements récurrents matin
                                        if (
                                            hoursDay[d] != null &&
                                            hoursDay[d][0] != null &&
                                            hoursDay[d][0] != "00:00:00" &&
                                            hoursDay[d][1] != null &&
                                            hoursDay[d][1] != "00:00:00"
                                        ) {
                                            eventsParse.push({
                                                startRecur: moment(
                                                    this.unavailableEvent[i]
                                                        .date
                                                )
                                                    .add(1, "d")
                                                    .format("YYYY-MM-DD"),
                                                endRecur: moment(
                                                    this.unavailableEvent[i]
                                                        .date_end
                                                ).format("YYYY-MM-DD"),
                                                startTime: hoursDay[d][0],
                                                endTime: hoursDay[d][1],
                                                daysOfWeek: [d],
                                                color: "#808080"
                                            });
                                        }
                                        //événements récurrents après-midi
                                        if (
                                            hoursDay[d] != null &&
                                            hoursDay[d][2] != null &&
                                            hoursDay[d][2] != "00:00:00" &&
                                            hoursDay[d][3] != null &&
                                            hoursDay[d][3] != "00:00:00"
                                        ) {
                                            eventsParse.push({
                                                startRecur: moment(
                                                    this.unavailableEvent[i]
                                                        .date
                                                )
                                                    .add(1, "d")
                                                    .format("YYYY-MM-DD"),
                                                endRecur: moment(
                                                    this.unavailableEvent[i]
                                                        .date_end
                                                ).format("YYYY-MM-DD"),
                                                startTime: hoursDay[d][2],
                                                endTime: hoursDay[d][3],
                                                daysOfWeek: [d],
                                                color: "#808080"
                                            });
                                        }
                                    }
                                    let nbDay = moment(
                                        this.unavailableEvent[i].date_end
                                    ).weekday();
                                    //dimanche = 7 -> 0 pour les événements récurrents (daysOfWeek)
                                    if (nbDay == 7) {
                                        nbDay = 0;
                                    }
                                    heureDebutMatin = hoursDay[nbDay][0];
                                    heureFinMatin = hoursDay[nbDay][1];
                                    heureDebutApresMidi = hoursDay[nbDay][2];
                                    heureFinApresMidi = hoursDay[nbDay][3];
                                }
                            }
                            //date de fin
                            if (
                                moment(
                                    this.unavailableEvent[i].date_end
                                ).format("HH:mm") <= heureFinMatin
                            ) {
                                eventsParse.push({
                                    start:
                                        moment(
                                            this.unavailableEvent[i].date_end
                                        ).format("YYYY-MM-DD") +
                                        " " +
                                        heureDebutMatin,
                                    end: this.unavailableEvent[i].date_end,
                                    color: "#808080"
                                });
                            } else {
                                eventsParse.push({
                                    start:
                                        moment(
                                            this.unavailableEvent[i].date_end
                                        ).format("YYYY-MM-DD") +
                                        " " +
                                        heureDebutMatin,
                                    end:
                                        moment(
                                            this.unavailableEvent[i].date_end
                                        ).format("YYYY-MM-DD") +
                                        " " +
                                        heureFinMatin,
                                    color: "#808080"
                                });
                                eventsParse.push({
                                    start:
                                        moment(
                                            this.unavailableEvent[i].date_end
                                        ).format("YYYY-MM-DD") +
                                        " " +
                                        heureDebutApresMidi,
                                    end: this.unavailableEvent[i].date_end,
                                    color: "#808080"
                                });
                            }

                            //date de début
                            if (
                                moment(this.unavailableEvent[i].date).format(
                                    "HH:mm"
                                ) < heureFinMatin
                            ) {
                                eventsParse.push({
                                    start: this.unavailableEvent[i].date,
                                    end:
                                        moment(
                                            this.unavailableEvent[i].date
                                        ).format("YYYY-MM-DD") +
                                        " " +
                                        heureFinMatin,
                                    color: "#808080"
                                });
                                eventsParse.push({
                                    start:
                                        moment(
                                            this.unavailableEvent[i].date
                                        ).format("YYYY-MM-DD") +
                                        " " +
                                        heureDebutApresMidi,
                                    end:
                                        moment(
                                            this.unavailableEvent[i].date
                                        ).format("YYYY-MM-DD") +
                                        " " +
                                        heureFinApresMidi,
                                    color: "#808080"
                                });
                            } else {
                                eventsParse.push({
                                    start: this.unavailableEvent[i].date,
                                    end:
                                        moment(
                                            this.unavailableEvent[i].date
                                        ).format("YYYY-MM-DD") +
                                        " " +
                                        heureFinApresMidi,
                                    color: "#808080"
                                });
                            }
                        }
                    }
                }
            } else if (this.$route.query.type === "users") {
                if (this.tasksEvent !== []) {
                    var task;
                    this.tasksEvent.forEach(t => {
                        // if (
                        //   t.user_id !== null &&
                        //   (t.user_id.toString() === this.$route.query.id ||
                        //     t.user_id === this.$route.query.id)
                        // ) {
                        // Get project id
                        // let project_id = null;
                        // this.$store.state.projectManagement.projects.forEach((p) => {
                        //   p.tasks_bundles.forEach((tb) => {
                        //     if (t.tasks_bundle_id === tb.id) {
                        //       project_id = tb.project_id;
                        //     }
                        //   });
                        // });
                        if (t.id == this.$route.query.task_id) {
                            task = t;
                        }
                    });
                    if (task != null) {
                        task.periods.forEach(p => {
                            let start_period_hour = moment(p.start_time).format(
                                "HH:mm"
                            );
                            let end_period_hour = moment(p.end_time).format(
                                "HH:mm"
                            );

                            minHour == null || start_period_hour < minHour
                                ? (minHour = start_period_hour)
                                : null;
                            maxHour == null || end_period_hour > maxHour
                                ? (maxHour = end_period_hour)
                                : null;

                            eventsParse.push({
                                id: task.id,
                                period_id: p.id,
                                title: task.name,
                                start: p.start_time,
                                estimated_time: task.estimated_time,
                                order: task.order,
                                description: task.description,
                                time_spent: task.time_spent,
                                workarea_id: task.workarea_id,
                                status: task.status,
                                end: p.end_time,
                                user_id: task.user_id,
                                project_id: task.project_id,
                                color: task.project.color
                            });
                        });
                    }
                }
                if (this.unavailableEvent != null) {
                    for (var i = 0; i < this.unavailableEvent.length; i++) {
                        if (
                            this.unavailableEvent[i].date_end != null &&
                            this.unavailableEvent[i].date !=
                                this.unavailableEvent[i].date_end
                        ) {
                            let workHours = this.$store.state.userManagement.users.find(
                                u => u.id == this.$route.query.id
                            );
                            let hoursDay = [];
                            let nbDay;
                            let heureDebutMatin;
                            let heureFinMatin;
                            let heureDebutApresMidi;
                            let heureFinApresMidi;

                            if (
                                workHours != null &&
                                workHours.work_hours != null
                            ) {
                                workHours = workHours.work_hours;
                                hoursDay.push(workHours[0]); //lundi
                                hoursDay.push(workHours[1]); //mardi
                                hoursDay.push(workHours[2]); //mercredi
                                hoursDay.push(workHours[3]); //jeudi
                                hoursDay.push(workHours[4]); //vendredi
                                hoursDay.push(workHours[5]); //samedi
                                hoursDay.push(workHours[6]); //dimanche
                                if (hoursDay != null && hoursDay.length > 0) {
                                    for (var d = 0; d < hoursDay.length; d++) {
                                        let day = d + 1;
                                        //dimanche = 7 -> 0 pour les événements récurrents (daysOfWeek)
                                        if (day == 7) {
                                            day = 0;
                                        }
                                        //événements récurrents matin
                                        if (
                                            hoursDay[d] != null &&
                                            hoursDay[d]["morning_starts_at"] !=
                                                null &&
                                            hoursDay[d]["morning_starts_at"] !=
                                                "00:00:00" &&
                                            hoursDay[d]["morning_ends_at"] !=
                                                null &&
                                            hoursDay[d]["morning_ends_at"] !=
                                                "00:00:00"
                                        ) {
                                            eventsParse.push({
                                                startRecur: moment(
                                                    this.unavailableEvent[i]
                                                        .date
                                                )
                                                    .add(1, "d")
                                                    .format("YYYY-MM-DD"),
                                                endRecur: moment(
                                                    this.unavailableEvent[i]
                                                        .date_end
                                                ).format("YYYY-MM-DD"),
                                                startTime:
                                                    hoursDay[d][
                                                        "morning_starts_at"
                                                    ],
                                                endTime:
                                                    hoursDay[d][
                                                        "morning_ends_at"
                                                    ],
                                                daysOfWeek: [day],
                                                color: "#808080"
                                            });
                                        }
                                        //événements récurrents après-midi
                                        if (
                                            hoursDay[d] != null &&
                                            hoursDay[d][
                                                "afternoon_starts_at"
                                            ] != null &&
                                            hoursDay[d][
                                                "afternoon_starts_at"
                                            ] != "00:00:00" &&
                                            hoursDay[d]["afternoon_ends_at"] !=
                                                null &&
                                            hoursDay[d]["afternoon_ends_at"] !=
                                                "00:00:00"
                                        ) {
                                            eventsParse.push({
                                                startRecur: moment(
                                                    this.unavailableEvent[i]
                                                        .date
                                                )
                                                    .add(1, "d")
                                                    .format("YYYY-MM-DD"),
                                                endRecur: moment(
                                                    this.unavailableEvent[i]
                                                        .date_end
                                                ).format("YYYY-MM-DD"),
                                                startTime:
                                                    hoursDay[d][
                                                        "afternoon_starts_at"
                                                    ],
                                                endTime:
                                                    hoursDay[d][
                                                        "afternoon_ends_at"
                                                    ],
                                                daysOfWeek: [day],
                                                color: "#808080"
                                            });
                                        }
                                    }
                                    nbDay = moment(
                                        this.unavailableEvent[i].date_end
                                    ).weekday();
                                    //dimanche = 7 -> 0 pour les événements récurrents (daysOfWeek)
                                    if (nbDay == 7) {
                                        nbDay = 0;
                                    }
                                    heureDebutMatin =
                                        hoursDay[nbDay]["morning_starts_at"];
                                    heureFinMatin =
                                        hoursDay[nbDay]["morning_ends_at"];
                                    heureDebutApresMidi =
                                        hoursDay[nbDay]["afternoon_starts_at"];
                                    heureFinApresMidi =
                                        hoursDay[nbDay]["afternoon_ends_at"];
                                }
                            }

                            //date de fin
                            if (
                                moment(
                                    this.unavailableEvent[i].date_end
                                ).format("HH:mm") <= heureFinMatin
                            ) {
                                eventsParse.push({
                                    start:
                                        moment(
                                            this.unavailableEvent[i].date_end
                                        ).format("YYYY-MM-DD") +
                                        " " +
                                        heureDebutMatin,
                                    end: this.unavailableEvent[i].date_end,
                                    color: "#808080"
                                });
                            } else {
                                eventsParse.push({
                                    start:
                                        moment(
                                            this.unavailableEvent[i].date_end
                                        ).format("YYYY-MM-DD") +
                                        " " +
                                        heureDebutMatin,
                                    end:
                                        moment(
                                            this.unavailableEvent[i].date_end
                                        ).format("YYYY-MM-DD") +
                                        " " +
                                        heureFinMatin,
                                    color: "#808080"
                                });
                                eventsParse.push({
                                    start:
                                        moment(
                                            this.unavailableEvent[i].date_end
                                        ).format("YYYY-MM-DD") +
                                        " " +
                                        heureDebutApresMidi,
                                    end: this.unavailableEvent[i].date_end,
                                    color: "#808080"
                                });
                            }

                            //date de début
                            if (
                                moment(this.unavailableEvent[i].date).format(
                                    "HH:mm"
                                ) < heureFinMatin
                            ) {
                                eventsParse.push({
                                    start: this.unavailableEvent[i].date,
                                    end:
                                        moment(
                                            this.unavailableEvent[i].date
                                        ).format("YYYY-MM-DD") +
                                        " " +
                                        heureFinMatin,
                                    color: "#808080"
                                });
                                eventsParse.push({
                                    start:
                                        moment(
                                            this.unavailableEvent[i].date
                                        ).format("YYYY-MM-DD") +
                                        " " +
                                        heureDebutApresMidi,
                                    end:
                                        moment(
                                            this.unavailableEvent[i].date
                                        ).format("YYYY-MM-DD") +
                                        " " +
                                        heureFinApresMidi,
                                    color: "#808080"
                                });
                            } else {
                                eventsParse.push({
                                    start: this.unavailableEvent[i].date,
                                    end:
                                        moment(
                                            this.unavailableEvent[i].date
                                        ).format("YYYY-MM-DD") +
                                        " " +
                                        heureFinApresMidi,
                                    color: "#808080"
                                });
                            }
                        }
                    }
                }
            } else if (this.$route.query.type === "workarea") {
                if (this.tasksEvent !== []) {
                    var task;
                    this.tasksEvent.forEach(t => {
                        // if (
                        //   t.user_id !== null &&
                        //   (t.user_id.toString() === this.$route.query.id ||
                        //     t.user_id === this.$route.query.id)
                        // ) {
                        // Get project id
                        // let project_id = null;
                        // this.$store.state.projectManagement.projects.forEach((p) => {
                        //   p.tasks_bundles.forEach((tb) => {
                        //     if (t.tasks_bundle_id === tb.id) {
                        //       project_id = tb.project_id;
                        //     }
                        //   });
                        // });
                        if (t.id == this.$route.query.task_id) {
                            task = t;
                        }
                    });
                    if (task != null) {
                        //this.tasksEvent.forEach(t => {
                        // if (
                        //   t.workarea_id !== null &&
                        //   (t.workarea_id.toString() === this.$route.query.id ||
                        //     t.workarea_id === this.$route.query.id)
                        // ) {

                        task.periods.forEach(p => {
                            let start_period_hour = moment(p.start_time).format(
                                "HH:mm"
                            );
                            let end_period_hour = moment(p.end_time).format(
                                "HH:mm"
                            );

                            minHour == null || start_period_hour < minHour
                                ? (minHour = start_period_hour)
                                : null;
                            maxHour == null || end_period_hour > maxHour
                                ? (maxHour = end_period_hour)
                                : null;

                            eventsParse.push({
                                id: task.id,
                                period_id: p.id,
                                title: task.name,
                                start: p.start_time,
                                estimated_time: task.estimated_time,
                                order: task.order,
                                description: task.description,
                                time_spent: task.time_spent,
                                workarea_id: task.workarea_id,
                                status: task.status,
                                end: p.end_time,
                                user_id: task.user_id,
                                project_id: task.project_id,
                                color: task.project.color
                            });
                        });
                        // }
                        //});
                    }
                }
                if (this.unavailableEvent != null) {
                    let workHours;
                    for (var i = 0; i < this.unavailableEvent.length; i++) {
                        if (this.unavailableEvent[i].lundi != null) {
                            workHours = this.$store.state.projectManagement
                                .projects[i];
                        }
                    }
                    for (var i = 0; i < this.unavailableEvent.length; i++) {
                        if (
                            this.unavailableEvent[i].date_end != null &&
                            this.unavailableEvent[i].date !=
                                this.unavailableEvent[i].date_end &&
                            workHours != null
                        ) {
                            let hoursDay = [];
                            let heureDebutMatin;
                            let heureFinMatin;
                            let heureDebutApresMidi;
                            let heureFinApresMidi;
                            if (
                                this.$store.state.projectManagement.projects !=
                                    null &&
                                this.$store.state.projectManagement
                                    .projects[0] != null
                            ) {
                                hoursDay.push(workHours["dimanche"]);
                                hoursDay.push(workHours["lundi"]);
                                hoursDay.push(workHours["mardi"]);
                                hoursDay.push(workHours["mercredi"]);
                                hoursDay.push(workHours["jeudi"]);
                                hoursDay.push(workHours["vendredi"]);
                                hoursDay.push(workHours["samedi"]);
                                if (
                                    hoursDay != null &&
                                    hoursDay.length > 0 &&
                                    hoursDay[0] != null
                                ) {
                                    for (var d = 0; d < hoursDay.length; d++) {
                                        //événements récurrents matin
                                        if (
                                            hoursDay[d] != null &&
                                            hoursDay[d][0] != null &&
                                            hoursDay[d][0] != "00:00:00" &&
                                            hoursDay[d][1] != null &&
                                            hoursDay[d][1] != "00:00:00"
                                        ) {
                                            eventsParse.push({
                                                startRecur: moment(
                                                    this.unavailableEvent[i]
                                                        .date
                                                )
                                                    .add(1, "d")
                                                    .format("YYYY-MM-DD"),
                                                endRecur: moment(
                                                    this.unavailableEvent[i]
                                                        .date_end
                                                ).format("YYYY-MM-DD"),
                                                startTime: hoursDay[d][0],
                                                endTime: hoursDay[d][1],
                                                daysOfWeek: [d],
                                                color: "#808080"
                                            });
                                        }
                                        //événements récurrents après-midi
                                        if (
                                            hoursDay[d] != null &&
                                            hoursDay[d][2] != null &&
                                            hoursDay[d][2] != "00:00:00" &&
                                            hoursDay[d][3] != null &&
                                            hoursDay[d][3] != "00:00:00"
                                        ) {
                                            eventsParse.push({
                                                startRecur: moment(
                                                    this.unavailableEvent[i]
                                                        .date
                                                )
                                                    .add(1, "d")
                                                    .format("YYYY-MM-DD"),
                                                endRecur: moment(
                                                    this.unavailableEvent[i]
                                                        .date_end
                                                ).format("YYYY-MM-DD"),
                                                startTime: hoursDay[d][2],
                                                endTime: hoursDay[d][3],
                                                daysOfWeek: [d],
                                                color: "#808080"
                                            });
                                        }
                                    }
                                    let nbDay = moment(
                                        this.unavailableEvent[i].date_end
                                    ).weekday();
                                    //dimanche = 7 -> 0 pour les événements récurrents (daysOfWeek)
                                    if (nbDay == 7) {
                                        nbDay = 0;
                                    }
                                    heureDebutMatin = hoursDay[nbDay][0];
                                    heureFinMatin = hoursDay[nbDay][1];
                                    heureDebutApresMidi = hoursDay[nbDay][2];
                                    heureFinApresMidi = hoursDay[nbDay][3];
                                }
                            }
                            //date de fin
                            if (
                                moment(
                                    this.unavailableEvent[i].date_end
                                ).format("HH:mm") <= heureFinMatin
                            ) {
                                eventsParse.push({
                                    start:
                                        moment(
                                            this.unavailableEvent[i].date_end
                                        ).format("YYYY-MM-DD") +
                                        " " +
                                        heureDebutMatin,
                                    end: this.unavailableEvent[i].date_end,
                                    color: "#808080"
                                });
                            } else {
                                eventsParse.push({
                                    start:
                                        moment(
                                            this.unavailableEvent[i].date_end
                                        ).format("YYYY-MM-DD") +
                                        " " +
                                        heureDebutMatin,
                                    end:
                                        moment(
                                            this.unavailableEvent[i].date_end
                                        ).format("YYYY-MM-DD") +
                                        " " +
                                        heureFinMatin,
                                    color: "#808080"
                                });
                                eventsParse.push({
                                    start:
                                        moment(
                                            this.unavailableEvent[i].date_end
                                        ).format("YYYY-MM-DD") +
                                        " " +
                                        heureDebutApresMidi,
                                    end: this.unavailableEvent[i].date_end,
                                    color: "#808080"
                                });
                            }

                            //date de début
                            if (
                                moment(this.unavailableEvent[i].date).format(
                                    "HH:mm"
                                ) < heureFinMatin
                            ) {
                                eventsParse.push({
                                    start: this.unavailableEvent[i].date,
                                    end:
                                        moment(
                                            this.unavailableEvent[i].date
                                        ).format("YYYY-MM-DD") +
                                        " " +
                                        heureFinMatin,
                                    color: "#808080"
                                });
                                eventsParse.push({
                                    start:
                                        moment(
                                            this.unavailableEvent[i].date
                                        ).format("YYYY-MM-DD") +
                                        " " +
                                        heureDebutApresMidi,
                                    end:
                                        moment(
                                            this.unavailableEvent[i].date
                                        ).format("YYYY-MM-DD") +
                                        " " +
                                        heureFinApresMidi,
                                    color: "#808080"
                                });
                            } else {
                                eventsParse.push({
                                    start: this.unavailableEvent[i].date,
                                    end:
                                        moment(
                                            this.unavailableEvent[i].date
                                        ).format("YYYY-MM-DD") +
                                        " " +
                                        heureFinApresMidi,
                                    color: "#808080"
                                });
                            }
                        }
                    }
                }
            }

            this.minTime =
                minHour && minHour >= "02:00"
                    ? moment(minHour, "HH:mm")
                          .subtract(2, "hour")
                          .format("HH:mm")
                    : "00:00";
            this.maxTime =
                maxHour && maxHour <= "22:00"
                    ? moment(maxHour, "HH:mm")
                          .add(2, "hour")
                          .format("HH:mm")
                    : "24:00";

            this.$store.dispatch("scheduleManagement/addItems", eventsParse);
            return eventsParse;
        },
        tasksEvent() {
            return this.$store.state.taskManagement
                ? this.$store.state.taskManagement.tasks
                : [];
        },
        unavailableEvent() {
            return this.$store.state.projectManagement
                ? this.$store.state.projectManagement.projects
                : [];
        },
        project_data() {
            if (this.$route.query.type === "projects") {
                return this.$store.getters["projectManagement/getItem"](
                    parseInt(this.$route.query.id)
                );
            }
            return null;
        },
        user_data() {
            if (this.$route.query.type === "users") {
                return this.$store.getters["userManagement/getItem"](
                    parseInt(this.$route.query.id)
                );
            }
            return null;
        },
        workarea_data() {
            if (this.$route.query.type === "workarea") {
                return this.$store.getters["workareaManagement/getItem"](
                    parseInt(this.$route.query.id)
                );
            }
            return null;
        },
        scheduleTitle() {
            let title;
            if (this.$route.query.type === "projects") {
                if (this.project_data) {
                    title = "Planning du projet : " + this.project_data.name;
                }
            } else if (this.$route.query.type === "users") {
                if (this.user_data) {
                    title =
                        "Planning de l'utilisateur : " +
                        this.user_data.firstname +
                        " " +
                        this.user_data.lastname;
                }
            } else if (this.$route.query.type === "workarea") {
                if (this.workarea_data) {
                    title =
                        "Planning du pôle de production : " +
                        this.workarea_data.name;
                }
            }
            return title;
        }
    },
    methods: {
        toggleWeekends() {
            this.calendarWeekends = !this.calendarWeekends; // update a property
        },
        gotoPast() {
            let calendarApi = this.$refs.fullCalendar.getApi(); // from the ref="..."
            calendarApi.gotoDate("2000-01-01"); // call a method on the Calendar object
        },
        handleDateClick(arg) {
            this.activeAddPrompt = true;
            this.dateData = arg;
        },
        handleEventClick(arg) {
            var targetEvent = this.calendarEvents.find(
                event =>
                    event.period_id === arg.event._def.extendedProps.period_id
            );

            this.$store
                .dispatch("taskManagement/editItem", targetEvent)
                .catch(err => {
                    console.error(err);
                });
        },
        handleEventDrop(arg) {
            var itemTemp = this.calendarEvents.find(
                e => e.period_id === arg.event._def.extendedProps.period_id
            );
            var startPeriodTask = moment(arg.event.start).format(
                "YYYY-MM-DD HH:mm:ss"
            );
            var endPeriodTask = moment(arg.event.end).format(
                "YYYY-MM-DD HH:mm:ss"
            );
            //Parse new item to update task
            var erreur = false;
            //dates task_period avant déplacement
            var taskPeriod = null;
            if (this.tasksEvent != null) {
                for (var i = 0; i < this.tasksEvent.length; i++) {
                    if (this.tasksEvent[i].id == this.$route.query.task_id) {
                        for (
                            var j = 0;
                            j < this.tasksEvent[i].periods.length;
                            j++
                        ) {
                            if (
                                this.tasksEvent[i].periods[j].id ==
                                arg.event._def.extendedProps.period_id
                            ) {
                                taskPeriod = this.tasksEvent[i].periods[j];
                            }
                        }
                    }
                }
            }
            //indispos -> bloquer le déplacement sur les événements grisés
            if (this.unavailableEvent != null) {
                for (var i = 0; i < this.unavailableEvent.length; i++) {
                    if (
                        this.unavailableEvent[i].date_end != null &&
                        this.unavailableEvent[i].date !=
                            this.unavailableEvent[i].date_end
                    ) {
                        if (
                            (moment(startPeriodTask).isAfter(
                                moment(this.unavailableEvent[i].date)
                            ) &&
                                moment(startPeriodTask).isBefore(
                                    moment(this.unavailableEvent[i].date_end)
                                )) ||
                            (moment(endPeriodTask).isAfter(
                                moment(this.unavailableEvent[i].date)
                            ) &&
                                moment(endPeriodTask).isBefore(
                                    moment(this.unavailableEvent[i].date_end)
                                )) ||
                            ((moment(startPeriodTask).isBefore(
                                moment(this.unavailableEvent[i].date)
                            ) ||
                                moment(startPeriodTask).isSame(
                                    moment(this.unavailableEvent[i].date)
                                )) &&
                                (moment(endPeriodTask).isAfter(
                                    moment(this.unavailableEvent[i].date_end)
                                ) ||
                                    moment(endPeriodTask).isSame(
                                        moment(
                                            this.unavailableEvent[i].date_end
                                        )
                                    )))
                        ) {
                            erreur = true;
                            //on remet la task_period à sa date originale
                            arg.event.setDates(
                                taskPeriod.start_time,
                                taskPeriod.end_time
                            );
                            this.$vs.notify({
                                title: "Erreur",
                                text:
                                    "Vous ne pouvez pas déplacer cette période ici car il n'y a pas de ressources nécessaires.",
                                iconPack: "feather",
                                icon: "icon-alert-circle",
                                color: "danger",
                                time: 10000
                            });
                        }
                    }
                }
            }
            //bloquer le déplacement si task dépendantes
            let order = null;
            let taskCourante = null;
            if (this.tasksEvent != null && !this.moveAccepted) {
                for (var i = 0; i < this.tasksEvent.length; i++) {
                    if (this.tasksEvent[i].id == this.$route.query.task_id) {
                        order = this.tasksEvent[i].order;
                        taskCourante = this.tasksEvent[i];
                    }
                }
                //si avant la date de début ou après la date de livraison
                if (
                    taskCourante != null &&
                    (moment(startPeriodTask).isBefore(
                        moment(taskCourante.project.start_date)
                    ) ||
                        moment(endPeriodTask).isAfter(
                            moment(taskCourante.project.date)
                        ))
                ) {
                    //on remet la task_period à sa date originale
                    arg.event.setDates(
                        taskPeriod.start_time,
                        taskPeriod.end_time
                    );
                }
                for (var i = 0; i < this.tasksEvent.length; i++) {
                    if (this.tasksEvent[i].id != this.$route.query.task_id) {
                        //task dépendante après
                        if (
                            order != null &&
                            this.tasksEvent[i].order > order &&
                            moment(endPeriodTask).isAfter(
                                moment(this.tasksEvent[i].date)
                            )
                        ) {
                            erreur = true;
                            //on remet la task_period à sa date originale
                            arg.event.setDates(
                                taskPeriod.start_time,
                                taskPeriod.end_time
                            );
                            this.$vs.notify({
                                title: "Erreur",
                                text: `Vous ne pouvez pas déplacer cette période ici car cette tâche est dépendante et doit être faite avant la suivante ("${this.tasksEvent[i].date}")`,
                                iconPack: "feather",
                                icon: "icon-alert-circle",
                                color: "danger",
                                time: 10000
                            });
                        }
                        //task dépendante avant
                        else if (
                            order != null &&
                            this.tasksEvent[i].order < order &&
                            moment(startPeriodTask).isBefore(
                                moment(this.tasksEvent[i].date_end)
                            )
                        ) {
                            erreur = true;
                            //on remet la task_period à sa date originale
                            arg.event.setDates(
                                taskPeriod.start_time,
                                taskPeriod.end_time
                            );
                            this.$vs.notify({
                                title: "Erreur",
                                text: `Vous ne pouvez pas déplacer cette période ici car cette tâche est dépendante et doit être faite après la prédécente ("${this.tasksEvent[i].date_end}")`,
                                iconPack: "feather",
                                icon: "icon-alert-circle",
                                color: "danger",
                                time: 10000
                            });
                        }
                    }
                    //récupérer les tasks_period de la task courante
                    else if (
                        this.tasksEvent[i].id == this.$route.query.task_id
                    ) {
                        for (
                            var j = 0;
                            j < this.tasksEvent[i].periods.length;
                            j++
                        ) {
                            if (
                                this.tasksEvent[i].periods[j].id ==
                                arg.event._def.extendedProps.period_id
                            ) {
                                this.tasksPeriod = this.tasksEvent[i].periods;
                            }
                        }
                    }
                }
                //bloquer le déplacement de la task_period sur une autre task_period de la task courante
                if (this.tasksPeriod != null) {
                    for (var i = 0; i < this.tasksPeriod.length; i++) {
                        if (
                            this.tasksPeriod[i].start_time != null &&
                            this.tasksPeriod[i].end_time != null &&
                            this.tasksPeriod[i].id !=
                                arg.event._def.extendedProps.period_id
                        ) {
                            if (
                                (moment(startPeriodTask).isAfter(
                                    moment(this.tasksPeriod[i].start_time)
                                ) &&
                                    moment(startPeriodTask).isBefore(
                                        moment(this.tasksPeriod[i].end_time)
                                    )) ||
                                (moment(endPeriodTask).isAfter(
                                    moment(this.tasksPeriod[i].start_time)
                                ) &&
                                    moment(endPeriodTask).isBefore(
                                        moment(this.tasksPeriod[i].end_time)
                                    )) ||
                                ((moment(startPeriodTask).isBefore(
                                    moment(this.tasksPeriod[i].start_time)
                                ) ||
                                    moment(startPeriodTask).isSame(
                                        moment(this.tasksPeriod[i].start_time)
                                    )) &&
                                    (moment(endPeriodTask).isAfter(
                                        moment(this.tasksPeriod[i].end_time)
                                    ) ||
                                        moment(endPeriodTask).isSame(
                                            moment(this.tasksPeriod[i].end_time)
                                        )))
                            ) {
                                erreur = true;
                                //on remet la task_period à sa date originale
                                arg.event.setDates(
                                    taskPeriod.start_time,
                                    taskPeriod.end_time
                                );
                                this.$vs.notify({
                                    title: "Erreur",
                                    text:
                                        "Vous ne pouvez pas déplacer cette période ici car il y a déjà une période planifiée.",
                                    iconPack: "feather",
                                    icon: "icon-alert-circle",
                                    color: "danger",
                                    time: 10000
                                });
                            }
                        }
                    }
                }
            }
            //bloquer le déplacement si en dehors des heures de travail
            var item = {
                id: this.$route.query.id,
                type: this.$route.query.type,
                task_id: this.$route.query.task_id
            };
            let workHoursDay = null;
            this.$store
                .dispatch("projectManagement/workHoursPeriods", item)
                .then(data => {
                    moment.locale("fr");
                    workHoursDay =
                        data.payload[moment(arg.event.start).format("dddd")];
                    if (
                        ((workHoursDay[0] == "00:00:00" ||
                            workHoursDay[0] == null) &&
                            (workHoursDay[1] == "00:00:00" ||
                                workHoursDay[1] == null) &&
                            (workHoursDay[2] == "00:00:00" ||
                                workHoursDay[2] == null) &&
                            (workHoursDay[3] == "00:00:00" ||
                                workHoursDay[3] == null)) ||
                        (moment(moment(arg.event.start).format("HH:mm:ss"))
                            ._i >= moment(workHoursDay[0])._i &&
                            moment(moment(arg.event.start).format("HH:mm:ss"))
                                ._i < moment(workHoursDay[1])._i &&
                            moment(moment(arg.event.end).format("HH:mm:ss"))
                                ._i > moment(workHoursDay[1])._i) ||
                        (moment(moment(arg.event.start).format("HH:mm:ss"))
                            ._i >= moment(workHoursDay[1])._i &&
                            moment(moment(arg.event.end).format("HH:mm:ss"))
                                ._i <= moment(workHoursDay[2])._i) ||
                        (moment(moment(arg.event.start).format("HH:mm:ss"))
                            ._i >= moment(workHoursDay[0])._i &&
                            moment(moment(arg.event.start).format("HH:mm:ss"))
                                ._i < moment(workHoursDay[2])._i &&
                            moment(moment(arg.event.end).format("HH:mm:ss"))
                                ._i > moment(workHoursDay[2])._i) ||
                        (moment(moment(arg.event.end).format("HH:mm:ss"))._i >
                            moment(workHoursDay[1])._i &&
                            (workHoursDay[2] == "00:00:00" ||
                                workHoursDay[2] == null) &&
                            (workHoursDay[3] == "00:00:00" ||
                                workHoursDay[3] == null)) ||
                        (moment(moment(arg.event.start).format("HH:mm:ss"))._i <
                            moment(workHoursDay[2])._i &&
                            (workHoursDay[0] == "00:00:00" ||
                                workHoursDay[0] == null) &&
                            (workHoursDay[1] == "00:00:00" ||
                                workHoursDay[1] == null)) ||
                        moment(moment(arg.event.start).format("HH:mm:ss"))._i <
                            moment(workHoursDay[0])._i ||
                        moment(moment(arg.event.end).format("HH:mm:ss"))._i >
                            moment(workHoursDay[3])._i
                    ) {
                        erreur = true;
                        //on remet la task_period à sa date originale
                        arg.event.setDates(
                            taskPeriod.start_time,
                            taskPeriod.end_time
                        );
                        this.$vs.notify({
                            title: "Erreur",
                            text:
                                "Vous ne pouvez pas déplacer cette période ici car l'utilisateur ne travaille pas.",
                            iconPack: "feather",
                            icon: "icon-alert-circle",
                            color: "danger",
                            time: 10000
                        });
                    }
                    if (!erreur) {
                        var itemToSave = {
                            id: itemTemp.period_id,
                            start: startPeriodTask,
                            end: endPeriodTask,
                            type: this.$route.query.type,
                            task_id: this.$route.query.task_id
                        };
                        this.$vs.loading();
                        this.$store
                            .dispatch(
                                "taskManagement/updateTaskPeriod",
                                itemToSave
                            )
                            .then(data => {
                                this.tasksPeriod = data.payload.periods;
                                this.$vs.notify({
                                    title: "Modification d'une période",
                                    text: `modifiée avec succès`,
                                    iconPack: "feather",
                                    icon: "icon-alert-circle",
                                    color: "success"
                                });
                            })
                            .catch(error => {
                                this.$vs.notify({
                                    title: "Erreur",
                                    text: error.message,
                                    iconPack: "feather",
                                    icon: "icon-alert-circle",
                                    color: "danger",
                                    time: 10000
                                });
                            })
                            .finally(() => {
                                this.$vs.loading.close();
                            });
                    }
                });
        },
        handleEventResize(arg) {
            var itemTemp = this.calendarEvents.find(
                e => e.period_id === arg.event._def.extendedProps.period_id
            );
            //Parse new item to update task
            var startPeriodTask = moment(arg.event.start).format(
                "YYYY-MM-DD HH:mm:ss"
            );
            var endPeriodTask = moment(arg.event.end).format(
                "YYYY-MM-DD HH:mm:ss"
            );
            //Parse new item to update task
            var erreur = false;
            //dates task_period avant déplacement
            var taskPeriod = null;
            if (this.tasksEvent != null) {
                for (var i = 0; i < this.tasksEvent.length; i++) {
                    if (this.tasksEvent[i].id == this.$route.query.task_id) {
                        for (
                            var j = 0;
                            j < this.tasksEvent[i].periods.length;
                            j++
                        ) {
                            if (
                                this.tasksEvent[i].periods[j].id ==
                                arg.event._def.extendedProps.period_id
                            ) {
                                taskPeriod = this.tasksEvent[i].periods[j];
                            }
                        }
                    }
                }
            }
            //indispos -> bloquer le déplacement sur les événements grisés
            if (this.unavailableEvent != null) {
                for (var i = 0; i < this.unavailableEvent.length; i++) {
                    if (
                        this.unavailableEvent[i].date_end != null &&
                        this.unavailableEvent[i].date !=
                            this.unavailableEvent[i].date_end
                    ) {
                        if (
                            (moment(startPeriodTask).isAfter(
                                moment(this.unavailableEvent[i].date)
                            ) &&
                                moment(startPeriodTask).isBefore(
                                    moment(this.unavailableEvent[i].date_end)
                                )) ||
                            (moment(endPeriodTask).isAfter(
                                moment(this.unavailableEvent[i].date)
                            ) &&
                                moment(endPeriodTask).isBefore(
                                    moment(this.unavailableEvent[i].date_end)
                                )) ||
                            ((moment(startPeriodTask).isBefore(
                                moment(this.unavailableEvent[i].date)
                            ) ||
                                moment(startPeriodTask).isSame(
                                    moment(this.unavailableEvent[i].date)
                                )) &&
                                (moment(endPeriodTask).isAfter(
                                    moment(this.unavailableEvent[i].date_end)
                                ) ||
                                    moment(endPeriodTask).isSame(
                                        moment(
                                            this.unavailableEvent[i].date_end
                                        )
                                    )))
                        ) {
                            erreur = true;
                            //on remet la task_period à sa date originale
                            arg.event.setDates(
                                taskPeriod.start_time,
                                taskPeriod.end_time
                            );
                            this.$vs.notify({
                                title: "Erreur",
                                text:
                                    "Vous ne pouvez pas déplacer cette période ici car il n'y a pas de ressources nécessaires.",
                                iconPack: "feather",
                                icon: "icon-alert-circle",
                                color: "danger",
                                time: 10000
                            });
                        }
                    }
                }
            }
            //bloquer le déplacement si task dépendantes
            let order = null;
            let taskCourante = null;
            if (this.tasksEvent != null && !this.moveAccepted) {
                for (var i = 0; i < this.tasksEvent.length; i++) {
                    if (this.tasksEvent[i].id == this.$route.query.task_id) {
                        order = this.tasksEvent[i].order;
                        taskCourante = this.tasksEvent[i];
                    }
                }
                //si avant la date de début ou après la date de livraison
                if (
                    taskCourante != null &&
                    (moment(startPeriodTask).isBefore(
                        moment(taskCourante.project.start_date)
                    ) ||
                        moment(endPeriodTask).isAfter(
                            moment(taskCourante.project.date)
                        ))
                ) {
                    //on remet la task_period à sa date originale
                    arg.event.setDates(
                        taskPeriod.start_time,
                        taskPeriod.end_time
                    );
                }
                for (var i = 0; i < this.tasksEvent.length; i++) {
                    if (this.tasksEvent[i].id != this.$route.query.task_id) {
                        //task dépendante après
                        if (
                            order != null &&
                            this.tasksEvent[i].order > order &&
                            moment(endPeriodTask).isAfter(
                                moment(this.tasksEvent[i].date)
                            )
                        ) {
                            erreur = true;
                            //on remet la task_period à sa date originale
                            arg.event.setDates(
                                taskPeriod.start_time,
                                taskPeriod.end_time
                            );
                            this.$vs.notify({
                                title: "Erreur",
                                text: `Vous ne pouvez pas déplacer cette période ici car cette tâche est dépendante et doit être faite avant la suivante ("${this.tasksEvent[i].date}")`,
                                iconPack: "feather",
                                icon: "icon-alert-circle",
                                color: "danger",
                                time: 10000
                            });
                        }
                        //task dépendante avant
                        else if (
                            order != null &&
                            this.tasksEvent[i].order < order &&
                            moment(startPeriodTask).isBefore(
                                moment(this.tasksEvent[i].date_end)
                            )
                        ) {
                            erreur = true;
                            //on remet la task_period à sa date originale
                            arg.event.setDates(
                                taskPeriod.start_time,
                                taskPeriod.end_time
                            );
                            this.$vs.notify({
                                title: "Erreur",
                                text: `Vous ne pouvez pas déplacer cette période ici car cette tâche est dépendante et doit être faite après la prédécente ("${this.tasksEvent[i].date_end}")`,
                                iconPack: "feather",
                                icon: "icon-alert-circle",
                                color: "danger",
                                time: 10000
                            });
                        }
                    }
                    //récupérer les tasks_period de la task courante
                    else if (
                        this.tasksEvent[i].id == this.$route.query.task_id
                    ) {
                        for (
                            var j = 0;
                            j < this.tasksEvent[i].periods.length;
                            j++
                        ) {
                            if (
                                this.tasksEvent[i].periods[j].id ==
                                arg.event._def.extendedProps.period_id
                            ) {
                                this.tasksPeriod = this.tasksEvent[i].periods;
                            }
                        }
                    }
                }
                //bloquer le déplacement de la task_period sur une autre task_period de la task courante
                if (this.tasksPeriod != null) {
                    for (var i = 0; i < this.tasksPeriod.length; i++) {
                        if (
                            this.tasksPeriod[i].start_time != null &&
                            this.tasksPeriod[i].end_time != null &&
                            this.tasksPeriod[i].id !=
                                arg.event._def.extendedProps.period_id
                        ) {
                            if (
                                (moment(startPeriodTask).isAfter(
                                    moment(this.tasksPeriod[i].start_time)
                                ) &&
                                    moment(startPeriodTask).isBefore(
                                        moment(this.tasksPeriod[i].end_time)
                                    )) ||
                                (moment(endPeriodTask).isAfter(
                                    moment(this.tasksPeriod[i].start_time)
                                ) &&
                                    moment(endPeriodTask).isBefore(
                                        moment(this.tasksPeriod[i].end_time)
                                    )) ||
                                ((moment(startPeriodTask).isBefore(
                                    moment(this.tasksPeriod[i].start_time)
                                ) ||
                                    moment(startPeriodTask).isSame(
                                        moment(this.tasksPeriod[i].start_time)
                                    )) &&
                                    (moment(endPeriodTask).isAfter(
                                        moment(this.tasksPeriod[i].end_time)
                                    ) ||
                                        moment(endPeriodTask).isSame(
                                            moment(this.tasksPeriod[i].end_time)
                                        )))
                            ) {
                                erreur = true;
                                //on remet la task_period à sa date originale
                                arg.event.setDates(
                                    taskPeriod.start_time,
                                    taskPeriod.end_time
                                );
                                this.$vs.notify({
                                    title: "Erreur",
                                    text:
                                        "Vous ne pouvez pas déplacer cette période ici car il y a déjà une période planifiée.",
                                    iconPack: "feather",
                                    icon: "icon-alert-circle",
                                    color: "danger",
                                    time: 10000
                                });
                            }
                        }
                    }
                }
            }
            //bloquer le déplacement si en dehors des heures de travail
            var item = {
                id: this.$route.query.id,
                type: this.$route.query.type,
                task_id: this.$route.query.task_id
            };
            let workHoursDay = null;
            this.$store
                .dispatch("projectManagement/workHoursPeriods", item)
                .then(data => {
                    moment.locale("fr");
                    workHoursDay =
                        data.payload[moment(arg.event.start).format("dddd")];
                    if (
                        ((workHoursDay[0] == "00:00:00" ||
                            workHoursDay[0] == null) &&
                            (workHoursDay[1] == "00:00:00" ||
                                workHoursDay[1] == null) &&
                            (workHoursDay[2] == "00:00:00" ||
                                workHoursDay[2] == null) &&
                            (workHoursDay[3] == "00:00:00" ||
                                workHoursDay[3] == null)) ||
                        (moment(moment(arg.event.start).format("HH:mm:ss"))
                            ._i >= moment(workHoursDay[0])._i &&
                            moment(moment(arg.event.start).format("HH:mm:ss"))
                                ._i < moment(workHoursDay[1])._i &&
                            moment(moment(arg.event.end).format("HH:mm:ss"))
                                ._i > moment(workHoursDay[1])._i) ||
                        (moment(moment(arg.event.start).format("HH:mm:ss"))
                            ._i >= moment(workHoursDay[1])._i &&
                            moment(moment(arg.event.end).format("HH:mm:ss"))
                                ._i <= moment(workHoursDay[2])._i) ||
                        (moment(moment(arg.event.start).format("HH:mm:ss"))
                            ._i >= moment(workHoursDay[0])._i &&
                            moment(moment(arg.event.start).format("HH:mm:ss"))
                                ._i < moment(workHoursDay[2])._i &&
                            moment(moment(arg.event.end).format("HH:mm:ss"))
                                ._i > moment(workHoursDay[2])._i) ||
                        (moment(moment(arg.event.end).format("HH:mm:ss"))._i >
                            moment(workHoursDay[1])._i &&
                            (workHoursDay[2] == "00:00:00" ||
                                workHoursDay[2] == null) &&
                            (workHoursDay[3] == "00:00:00" ||
                                workHoursDay[3] == null)) ||
                        (moment(moment(arg.event.start).format("HH:mm:ss"))._i <
                            moment(workHoursDay[2])._i &&
                            (workHoursDay[0] == "00:00:00" ||
                                workHoursDay[0] == null) &&
                            (workHoursDay[1] == "00:00:00" ||
                                workHoursDay[1] == null)) ||
                        moment(moment(arg.event.start).format("HH:mm:ss"))._i <
                            moment(workHoursDay[0])._i ||
                        moment(moment(arg.event.end).format("HH:mm:ss"))._i >
                            moment(workHoursDay[3])._i
                    ) {
                        erreur = true;
                        //on remet la task_period à sa date originale
                        arg.event.setDates(
                            taskPeriod.start_time,
                            taskPeriod.end_time
                        );
                        this.$vs.notify({
                            title: "Erreur",
                            text:
                                "Vous ne pouvez pas déplacer cette période ici car l'utilisateur ne travaille pas.",
                            iconPack: "feather",
                            icon: "icon-alert-circle",
                            color: "danger",
                            time: 10000
                        });
                    }
                    if (!erreur) {
                        var itemToSave = {
                            id: itemTemp.period_id,
                            start: startPeriodTask,
                            end: endPeriodTask,
                            type: this.$route.query.type,
                            task_id: this.$route.query.task_id
                        };
                        this.$vs.loading();
                        this.$store
                            .dispatch(
                                "taskManagement/updateTaskPeriod",
                                itemToSave
                            )
                            .then(data => {
                                this.tasksPeriod = data.payload.periods;
                                this.$vs.notify({
                                    title: "Modification d'une période",
                                    text: `modifiée avec succès`,
                                    iconPack: "feather",
                                    icon: "icon-alert-circle",
                                    color: "success"
                                });
                            })
                            .catch(error => {
                                this.$vs.notify({
                                    title: "Erreur",
                                    text: error.message,
                                    iconPack: "feather",
                                    icon: "icon-alert-circle",
                                    color: "danger"
                                });
                            })
                            .finally(() => {
                                this.$vs.loading.close();
                            });
                    }
                });
        },
        handleClose() {
            //this.refresh();
            (this.activeAddPrompt = false), (this.dateData = {});
        },
        authorizedTo(action, model = modelPlurial) {
            return this.$store.getters.userHasPermissionTo(
                `${action} ${model}`
            );
        },
        getBusinessHours() {
            if (this.$route.query.type === "projects") {
                let businessHours = [];
                var item = {
                    id: this.$route.query.id,
                    type: this.$route.query.type,
                    task_id: this.$route.query.task_id
                };
                this.$store
                    .dispatch("projectManagement/workHoursPeriods", item)

                    .then(data => {
                        var dates = [];
                        var hoursOtherProjects = [];
                        dates.push(data.payload.dimanche);
                        dates.push(data.payload.lundi);
                        dates.push(data.payload.mardi);
                        dates.push(data.payload.mercredi);
                        dates.push(data.payload.jeudi);
                        dates.push(data.payload.vendredi);
                        dates.push(data.payload.samedi);
                        //hoursOtherProjects.push(data.payload.otherProjects);

                        var days = [
                            "dimanche",
                            "lundi",
                            "mardi",
                            "mercredi",
                            "jeudi",
                            "vendredi",
                            "samedi"
                        ];
                        //hoursOtherProjects=hoursOtherProjects[0];
                        var i = 0;
                        if (dates != []) {
                            let businessHours = [];
                            dates.forEach(day => {
                                //hoursOtherProjects.forEach((period) => {
                                if (
                                    day[0] !== null &&
                                    day[0] != "00:00:00" &&
                                    day[1] !== null && day[1] != "00:00:00"
                                ) {
                                    businessHours.push({
                                        daysOfWeek: this.getDayNumber(days[i]),
                                        startTime: day[0],
                                        endTime: day[1]
                                    });
                                }
                                if (
                                    day[2] !== null &&
                                    day[2] != "00:00:00" &&
                                    day[3] !== null && day[3] != "00:00:00"
                                ) {
                                    businessHours.push({
                                        daysOfWeek: this.getDayNumber(days[i]),
                                        startTime: day[2],
                                        endTime: day[3]
                                    });
                                }
                                i = i + 1;
                                //});
                            });
                            if (businessHours !== []) {
                                let minHour = null;
                                let maxHour = null;
                                businessHours.forEach(bH => {
                                    if (
                                        bH.startTime != null &&
                                        bH.endTime != null
                                    ) {
                                        if (
                                            minHour === null ||
                                            minHour > bH.startTime
                                        ) {
                                            minHour = bH.startTime;
                                        }
                                        if (
                                            maxHour === null ||
                                            maxHour < bH.endTime
                                        ) {
                                            maxHour = bH.endTime;
                                        }
                                    } else if (
                                        bH.start != null &&
                                        bH.end != null
                                    ) {
                                        let start = moment(bH.start).format(
                                            "HH:mm"
                                        );
                                        let end = moment(bH.end).format(
                                            "HH:mm"
                                        );
                                        if (
                                            minHour === null ||
                                            minHour > start
                                        ) {
                                            minHour = start;
                                        }
                                        if (maxHour === null || maxHour < end) {
                                            maxHour = end;
                                        }
                                    }
                                });

                                this.minTime =
                                    minHour >= "02:00"
                                        ? moment(minHour, "HH:mm")
                                              .subtract(2, "hour")
                                              .format("HH:mm")
                                        : "00:00";
                                this.maxTime =
                                    maxHour <= "22:00"
                                        ? moment(maxHour, "HH:mm")
                                              .add(2, "hour")
                                              .format("HH:mm")
                                        : "24:00";

                                this.businessHours = businessHours;
                            } else {
                                this.businessHours = false;
                            }
                        } else {
                            this.businessHours = false;
                        }
                    });
            } else if (this.$route.query.type === "users") {
                this.$store
                    .dispatch("userManagement/fetchItem", this.$route.query.id)
                    .then(data => {
                        let item = data.payload;
                        if (item.work_hours.length > 0) {
                            let businessHours = [];
                            item.work_hours.forEach(wH => {
                                if (wH.is_active === 1) {
                                    if (
                                        wH.morning_starts_at !== null &&
                                        wH.morning_ends_at !== null
                                    ) {
                                        businessHours.push({
                                            daysOfWeek: this.getDayNumber(
                                                wH.day
                                            ),
                                            startTime: this.parseWorkHour(
                                                wH.morning_starts_at
                                            ),
                                            endTime: this.parseWorkHour(
                                                wH.morning_ends_at
                                            )
                                        });
                                    }
                                    if (
                                        wH.afternoon_starts_at !== null &&
                                        wH.afternoon_ends_at !== null
                                    ) {
                                        businessHours.push({
                                            daysOfWeek: this.getDayNumber(
                                                wH.day
                                            ),
                                            startTime: this.parseWorkHour(
                                                wH.afternoon_starts_at
                                            ),
                                            endTime: this.parseWorkHour(
                                                wH.afternoon_ends_at
                                            )
                                        });
                                    }
                                }
                            });
                            if (businessHours !== []) {
                                let minHour = null;
                                let maxHour = null;
                                businessHours.forEach(bH => {
                                    if (
                                        minHour === null ||
                                        minHour > bH.startTime
                                    ) {
                                        minHour = bH.startTime;
                                    }
                                    if (
                                        maxHour === null ||
                                        maxHour < bH.endTime
                                    ) {
                                        maxHour = bH.endTime;
                                    }
                                });

                                this.minTime =
                                    minHour >= "02:00"
                                        ? moment(minHour, "HH:mm")
                                              .subtract(2, "hour")
                                              .format("HH:mm")
                                        : "00:00";
                                this.maxTime =
                                    maxHour <= "22:00"
                                        ? moment(maxHour, "HH:mm")
                                              .add(2, "hour")
                                              .format("HH:mm")
                                        : "24:00";

                                this.businessHours = businessHours;
                            } else {
                                this.businessHours = false;
                            }
                        } else {
                            this.businessHours = false;
                        }
                    });
            } else if (this.$route.query.type === "workarea") {
                let businessHours = [];
                var item = {
                    id: this.$route.query.id,
                    type: this.$route.query.type,
                    task_id: this.$route.query.task_id
                };
                this.$store
                    .dispatch("projectManagement/workHoursPeriods", item)

                    .then(data => {
                        var dates = [];
                        dates.push(data.payload.dimanche);
                        dates.push(data.payload.lundi);
                        dates.push(data.payload.mardi);
                        dates.push(data.payload.mercredi);
                        dates.push(data.payload.jeudi);
                        dates.push(data.payload.vendredi);
                        dates.push(data.payload.samedi);
                        var days = [
                            "dimanche",
                            "lundi",
                            "mardi",
                            "mercredi",
                            "jeudi",
                            "vendredi",
                            "samedi"
                        ];
                        var i = 0;
                        if (dates != []) {
                            let businessHours = [];
                            dates.forEach(day => {
                                //if (wH.is_active === 1) {
                                if (
                                    // wH.morning_starts_at !== null &&
                                    // wH.morning_ends_at !== null
                                    day[0] !== null &&
                                    day[0] != "00:00:00" &&
                                    day[1] !== null && day[1] != "00:00:00"
                                ) {
                                    businessHours.push({
                                        daysOfWeek: this.getDayNumber(days[i]),
                                        startTime: day[0],
                                        endTime: day[1]
                                    });
                                }
                                if (
                                    day[2] !== null &&
                                    day[2] != "00:00:00" &&
                                    day[3] !== null && day[3] != "00:00:00"
                                ) {
                                    businessHours.push({
                                        daysOfWeek: this.getDayNumber(days[i]),
                                        startTime: day[2],
                                        endTime: day[3]
                                    });
                                }
                                //}
                                i = i + 1;
                            });
                            if (businessHours !== []) {
                                let minHour = null;
                                let maxHour = null;
                                businessHours.forEach(bH => {
                                    if (
                                        minHour === null ||
                                        minHour > bH.startTime
                                    ) {
                                        minHour = bH.startTime;
                                    }
                                    if (
                                        maxHour === null ||
                                        maxHour < bH.endTime
                                    ) {
                                        maxHour = bH.endTime;
                                    }
                                });

                                this.minTime =
                                    minHour >= "02:00"
                                        ? moment(minHour, "HH:mm")
                                              .subtract(2, "hour")
                                              .format("HH:mm")
                                        : "00:00";
                                this.maxTime =
                                    maxHour <= "22:00"
                                        ? moment(maxHour, "HH:mm")
                                              .add(2, "hour")
                                              .format("HH:mm")
                                        : "24:00";

                                this.businessHours = businessHours;
                            } else {
                                this.businessHours = false;
                            }
                        } else {
                            this.businessHours = false;
                        }
                    });
            } else {
                this.businessHours = false;
            }
        },
        parseWorkHour(hour) {
            let splitHour = hour.split(":");
            let parseHour = splitHour[0] + ":" + splitHour[1];
            return parseHour;
        },
        getDayNumber(day) {
            let dayNumber = [];
            switch (day) {
                case "lundi":
                    dayNumber.push(1);
                    break;
                case "mardi":
                    dayNumber.push(2);
                    break;
                case "mercredi":
                    dayNumber.push(3);
                    break;
                case "jeudi":
                    dayNumber.push(4);
                    break;
                case "vendredi":
                    dayNumber.push(5);
                    break;
                case "samedi":
                    dayNumber.push(6);
                    break;
                case "dimanche":
                    dayNumber.push(0);
                    break;

                default:
                    dayNumber = [];
                    break;
            }
            return dayNumber;
        },
        dateCalendar() {
            if (
                this.tasksEvent !== [] &&
                this.tasksEvent[0] != null &&
                this.$refs.fullCalendar != null
            ) {
                for (let i = 0; i < this.tasksEvent.length; i++) {
                    if (this.tasksEvent[i].id == this.$route.query.task_id) {
                        this.date = moment(this.tasksEvent[i].date).format(
                            "YYYY-MM-DD"
                        );
                        this.$refs.fullCalendar.getApi().gotoDate(this.date);
                    }
                }
            }
        }
    },
    created() {
        // Add store management
        if (!moduleScheduleManagement.isRegistered) {
            this.$store.registerModule(
                "scheduleManagement",
                moduleScheduleManagement
            );
            moduleScheduleManagement.isRegistered = true;
        }
        if (!moduleTaskManagement.isRegistered) {
            this.$store.registerModule("taskManagement", moduleTaskManagement);
            moduleTaskManagement.isRegistered = true;
        }
        if (!moduleProjectManagement.isRegistered) {
            this.$store.registerModule(
                "projectManagement",
                moduleProjectManagement
            );
            moduleProjectManagement.isRegistered = true;
        }
        if (!moduleSkillManagement.isRegistered) {
            this.$store.registerModule(
                "skillManagement",
                moduleSkillManagement
            );
            moduleSkillManagement.isRegistered = true;
        }
        if (!moduleUserManagement.isRegistered) {
            this.$store.registerModule("userManagement", moduleUserManagement);
            moduleUserManagement.isRegistered = true;
        }
        if (!moduleWorkareaManagement.isRegistered) {
            this.$store.registerModule(
                "workareaManagement",
                moduleWorkareaManagement
            );
            moduleWorkareaManagement.isRegistered = true;
        }
        if (!moduleDocumentManagement.isRegistered) {
            this.$store.registerModule(
                "documentManagement",
                moduleDocumentManagement
            );
            moduleDocumentManagement.isRegistered = true;
        }

        //this.$store.state.taskManagement.tasks = [];

        const item = {
            id: this.$route.query.id,
            type: this.$route.query.type,
            task_id: this.$route.query.task_id
        };
        if (this.$route.query.type === "projects") {
            this.$store
                .dispatch("taskManagement/fetchItems", {
                    project_id: item.id
                })
                .then(data => {
                    this.dateCalendar();
                });
            this.$store.dispatch("projectManagement/fetchItem", item.id);
        } else if (this.$route.query.type === "users") {
            this.$store
                .dispatch("taskManagement/fetchItems", {
                    user_id: this.$route.query.id
                })
                .then(data => {
                    this.dateCalendar();
                });
            this.$store.dispatch("userManagement/fetchItem", item.id);
        } else if (this.$route.query.type === "workarea") {
            this.$store
                .dispatch("taskManagement/fetchItems", {
                    workarea_id: this.$route.query.id
                })
                .then(data => {
                    this.dateCalendar();
                });
            this.$store.dispatch("workareaManagement/fetchItem", item.id);
        }
        this.$store.dispatch("projectManagement/unavailablePeriods", item);

        this.$store.dispatch("skillManagement/fetchItems", {
            order_by: "name"
        });
        if (this.authorizedTo("read", "users")) {
            this.$store.dispatch("userManagement/fetchItems");
        }
        if (
            this.$route.query.type === "users" ||
            this.$route.query.type === "projects" ||
            this.$route.query.type === "workarea"
        ) {
            this.getBusinessHours();
        }
    },
    updated() {
        // if (this.$route.query.type === "projects") {
        //   var id_bundle = null;
        //   this.$store.state.projectManagement.projects.forEach(p => {
        //     p.tasks_bundles.forEach(t => {
        //       if (t.project_id === this.$route.query.id) {
        //         id_bundle = t.id;
        //       }
        //     });
        //   });
        //   if (id_bundle != null) {
        //     this.$store
        //       .dispatch("taskManagement/fetchItems", {tasks_bundle_id: id_bundle})
        //       .catch(err => {
        //         this.manageErrors(err);
        //       });
        //   }
        // } else if (this.$route.query.type === "users") {
        //   this.$store.dispatch("taskManagement/fetchItems").catch(err => {
        //     this.manageErrors(err);
        //   });
        // } else if (this.$route.query.type === "workarea") {
        //   this.$store.dispatch("taskManagement/fetchItems").catch(err => {
        //     this.manageErrors(err);
        //   });
        // }
        // this.$store.dispatch("skillManagement/fetchItems").catch(err => {
        //   this.manageErrors(err);
        // });
    },
    beforeDestroy() {
        moduleScheduleManagement.isRegistered = false;
        moduleTaskManagement.isRegistered = false;
        moduleSkillManagement.isRegistered = false;
        moduleDocumentManagement.isRegistered = false;
        moduleProjectManagement.isRegistered = false;
        this.$store.unregisterModule("scheduleManagement");
        this.$store.unregisterModule("taskManagement");
        this.$store.unregisterModule("skillManagement");
        this.$store.unregisterModule("documentManagement");
        this.$store.unregisterModule("projectManagement");
    }
};
</script>

<style>
.demo-app {
    font-family: Arial, Helvetica Neue, Helvetica, sans-serif;
    font-size: 14px;
}
.demo-app-top {
    margin: 0 0 3em;
}
.demo-app-calendar {
    margin: 0 auto;
    max-width: 100%;
}

.fc-timeGridDay-button {
    background-color: rgb(55, 136, 216);
    border-color: rgb(55, 136, 216);
}

.fc-button {
    background-color: rgb(55, 136, 216);
    border-color: rgb(55, 136, 216);
}

.fc-button-primary {
    background-color: rgb(55, 136, 216);
    border-color: rgb(55, 136, 216);
}

.fc-button-active {
    background-color: rgb(32, 70, 168);
    border-color: rgb(32, 71, 168);
}

.fc-button:hover {
    background-color: rgb(40, 61, 116);
    text-decoration: none;
}

.btnBack {
    line-height: 2;
}
</style>
