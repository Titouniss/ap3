<template>
    <div>
        <router-link
            :to="'/hours'"
            class="btnBack flex cursor-pointer text-inherit hover:text-primary p-3 mb-3"
        >
            <feather-icon class="'h-5 w-5" icon="ArrowLeftIcon"></feather-icon>
            <span class="ml-2">Retour aux heures</span>
        </router-link>

        <div v-if="isAdmin || isManager" class="vx-card p-6 mt-3 mb-5">
            <div class="d-theme-dark-light-bg flex flex-row justify-start pb-3">
                <feather-icon icon="FilterIcon" svgClasses="h-6 w-6" />
                <h4 class="ml-3">Filtres</h4>
            </div>
            <div class="flex flex-wrap justify-center items-end">
                <div v-if="isAdmin" class="mr-10" style="min-width: 15em">
                    <v-select
                        label="name"
                        v-model="filters.company"
                        :options="companiesData"
                        @input="refreshDataUsers"
                        class="w-full"
                    >
                        <template #header>
                            <div style="opacity: 0.8">Société</div>
                        </template>
                    </v-select>
                </div>
                <div style="min-width: 15em">
                    <v-select
                        label="lastname"
                        v-model="filters.user"
                        :options="usersData"
                        @input="refreshDataCalendar"
                        class="w-full"
                    >
                        <!-- Finir le filtre -->
                        <template #header>
                            <div style="opacity: 0.8">Utilisateur</div>
                        </template>
                        <template #option="user">
                            <span>
                                {{ `${user.lastname} ${user.firstname}` }}
                            </span>
                        </template>
                    </v-select>
                </div>
            </div>
        </div>

        <div class="vx-card w-full p-6" v-if="filters.user">
            <add-form
                :activeAddPrompt="activeAddPrompt"
                :clickDate="dateData"
                :hours_list="hoursData"
                :handleClose="handleClose"
                :user="filters.user"
            />

            <FullCalendar
                locale="fr"
                class="demo-app-calendar border-c"
                ref="fullCalendar"
                defaultView="timeGridWeek"
                :editable="true"
                :droppable="false"
                :header="{
                    left: 'prev today next',
                    center: 'dayGridMonth, timeGridWeek, timeGridDay',
                    right: 'title'
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
                :firstDay="1"
                :minTime="minTime"
                :maxTime="maxTime"
                contentHeight="auto"
                :weekNumbers="true"
                :businessHours="businessHours"
                :events="calendarEvents"
                @eventDrop="handleEventDrop"
                @dateClick="handleDateClick"
                @eventClick="handleEventClick"
                @eventResize="handleEventResize"
                @datesRender="fetchHours"
            />
            <edit-form
                :reload="calendarEvents"
                :itemId="itemIdToEdit"
                :company="filters.company"
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

// Store Module
import moduleHourManagement from "@/store/hours-management/moduleHoursManagement.js";
import moduleProjectManagement from "@/store/project-management/moduleProjectManagement.js";
import moduleUserManagement from "@/store/user-management/moduleUserManagement.js";
import moduleCompanyManagement from "@/store/company-management/moduleCompanyManagement.js";
import moduleUnavailabilityManagement from "@/store/unavailability-management/moduleUnavailabilityManagement.js";

// Component
import EditForm from "./EditForm.vue";
import AddForm from "./AddForm.vue";

// must manually include stylesheets for each plugin
import "@fullcalendar/core/main.css";
import "@fullcalendar/daygrid/main.css";
import "@fullcalendar/timegrid/main.css";
import { colors } from "../../../themeConfig";

var model = "schedule";
var modelPlurial = "schedules";
var modelTitle = "Plannings";

export default {
    components: {
        FullCalendar,
        vSelect,
        AddForm,
        EditForm
    },
    data() {
        return {
            calendarPlugins: [
                // plugins must be defined in the JS
                dayGridPlugin,
                timeGridPlugin,
                interactionPlugin // needed for dateClick
            ],
            activeAddPrompt: false,
            scheduleTitle: "",
            dateData: {},
            taskBundle: null,
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
            minTime: "00:00",
            maxTime: "24:00",
            // Filters
            filters: {
                company: null,
                user: null
            }
        };
    },
    computed: {
        isAdmin() {
            return this.$store.state.AppActiveUser.is_admin;
        },
        isManager() {
            return this.$store.state.AppActiveUser.is_manager;
        },
        itemIdToEdit() {
            const item = this.$store.getters["hoursManagement/getSelectedItem"];
            return item ? item.id : -1;
        },
        calendarEvents() {
            let finalHours = [];
            let hours = this.filters.user
                ? this.$store.getters[
                      "hoursManagement/getCalendarItems"
                  ].filter(item => item.user_id === this.filters.user.id)
                : [];
            finalHours = hours;

            let paidHolidays = this.$store.getters[
                "unavailabilityManagement/getItems"
            ].filter(item => item.reason === "Congés payés");
            paidHolidays.forEach(pH => {
                finalHours.push({
                    color: "#AEAEAE ",
                    title: "Congés payés",
                    end: pH.ends_at,
                    start: pH.starts_at,
                    user_id: this.filters.user.id
                });
            });
            paidHolidays = this.$store.getters[
                "unavailabilityManagement/getItems"
            ].filter(item => item.reason === "Jours fériés");
            paidHolidays.forEach(pH => {
                finalHours.push({
                    color: "#AEAEAE ",
                    title: "Jours fériés",
                    end: pH.ends_at,
                    start: pH.starts_at,
                    user_id: this.filters.user.id
                });
            });
            paidHolidays = this.$store.getters[
                "unavailabilityManagement/getItems"
            ].filter(item => item.reason === "Période de cours");
            paidHolidays.forEach(pH => {
                finalHours.push({
                    color: "#AEAEAE ",
                    title: "Période de cours",
                    end: pH.ends_at,
                    start: pH.starts_at,
                    user_id: this.filters.user.id
                });
            });
            paidHolidays = this.$store.getters[
                "unavailabilityManagement/getItems"
            ].filter(
                item => item.reason === "Utilisation heures supplémentaires"
            );
            paidHolidays.forEach(pH => {
                finalHours.push({
                    color: "#AEAEAE ",
                    title: "Utilisation heures supplémentaires",
                    end: pH.ends_at,
                    start: pH.starts_at,
                    user_id: this.filters.user.id
                });
            });
            //   console.log("calendarEvents -> finalHours", finalHours);
            return finalHours;
        },
        companiesData() {
            const companies = JSON.parse(
                JSON.stringify(
                    this.$store.getters["companyManagement/getItems"]
                )
            );
            return companies.sort(function(a, b) {
                var textA = a.name.toUpperCase();
                var textB = b.name.toUpperCase();
                return textA < textB ? -1 : textA > textB ? 1 : 0;
            });
        },
        usersData() {
            const users = JSON.parse(
                JSON.stringify(this.$store.getters["userManagement/getItems"])
            );
            return this.filters.company
                ? users.filter(
                      item => item.company_id === this.filters.company.id
                  )
                : [];
        },
        hoursData() {
            return this.$store.getters["hoursManagement/getItems"];
        }
    },
    methods: {
        fetchHours() {
            const { user } = this.filters;
            if (user && this.$refs.fullCalendar) {
                const calendarApi = this.$refs.fullCalendar.getApi();
                const unit = calendarApi.view.viewSpec.singleUnit;
                const date = moment(calendarApi.getDate()).format("DD-MM-YYYY");
                // Refresh hours
                this.$vs.loading();
                this.$store
                    .dispatch("hoursManagement/fetchItems", {
                        date,
                        period_type: unit,
                        user_id: user.id
                    })
                    .finally(() => this.$vs.loading.close());

                // Refresh unavailabilities
                this.$vs.loading();
                this.$store
                    .dispatch("unavailabilityManagement/fetchItems", {
                        date,
                        period_type: unit,
                        user_id: user.id
                    })
                    .finally(() => this.$vs.loading.close());
            }
        },
        authorizedTo(action, model = "hours") {
            return this.$store.getters.userHasPermissionTo(
                `${action} ${model}`
            );
        },
        refresh() {},
        toggleWeekends() {
            this.calendarWeekends = !this.calendarWeekends; // update a property
        },
        gotoPast() {
            let calendarApi = this.$refs.fullCalendar.getApi(); // from the ref="..."
            calendarApi.gotoDate("2000-01-01"); // call a method on the Calendar object
        },
        handleDateClick(arg) {
            const period_start = moment(arg.dateStr).format(
                "YYYY-MM-DD HH:mm:ss"
            );
            const period_end = moment(arg.dateStr)
                .add(30, "m")
                .format("YYYY-MM-DD HH:mm:ss");

            var targetsEvent = this.calendarEvents.filter(
                item =>
                    moment(item.end).format("YYYY-MM-DD HH:mm:ss") >
                        period_start &&
                    moment(item.end).format("YYYY-MM-DD HH:mm:ss") < period_end
            );
            if (targetsEvent.length == 1) {
                arg.dateStr = targetsEvent[0].end;
            } else if (targetsEvent.length > 1) {
                let lastEvent = null;
                targetsEvent.forEach(element => {
                    if (lastEvent == null || element.end > lastEvent.end) {
                        lastEvent = element;
                    }
                });

                arg.dateStr = lastEvent.end;
            }

            this.dateData = arg;
            this.activeAddPrompt = true;
        },
        handleEventClick(arg) {
            var targetEvent = this.calendarEvents.find(
                event => event.id.toString() === arg.event.id
            );

            this.$store
                .dispatch("hoursManagement/editItem", targetEvent)
                .catch(err => {
                    console.error(err);
                });
        },
        handleEventResize(arg) {
            var itemTemp = this.calendarEvents.find(
                e => e.id.toString() === arg.event.id
            );

            var itemToSave = {
                id: itemTemp.id,
                description: itemTemp.description,
                start_at: moment(arg.event.start).format("YYYY-MM-DD HH:mm:ss"),
                end_at: moment(arg.event.end).format("YYYY-MM-DD HH:mm:ss"),
                user_id: itemTemp.user_id,
                project_id: itemTemp.project_id,
                date: moment(arg.event.start).format("YYYY-MM-DD")
            };
            this.$vs.loading();
            this.$store
                .dispatch("hoursManagement/updateItem", itemToSave)
                .then(data => {
                    //this.refresh();
                })
                .catch(err => {
                    console.error(err);
                })
                .finally(() => {
                    this.$vs.loading.close();
                });
        },
        handleEventDrop(arg) {
            var itemTemp = this.calendarEvents.find(
                e => e.id.toString() === arg.event.id
            );

            var itemToSave = {
                id: itemTemp.id,
                description: itemTemp.description,
                start_at: moment(arg.event.start).format("YYYY-MM-DD HH:mm:ss"),
                end_at: moment(arg.event.end).format("YYYY-MM-DD HH:mm:ss"),
                user_id: itemTemp.user_id,
                project_id: itemTemp.project_id,
                date: moment(arg.event.start).format("YYYY-MM-DD")
            };

            this.$vs.loading();

            this.$store
                .dispatch("hoursManagement/addItem", itemToSave)
                .then(data => {
                    this.$vs.notify({
                        title: "Ajout d'un horaire",
                        text: `Horaire ajouté avec succès`,
                        iconPack: "feather",
                        icon: "icon-alert-circle",
                        color: "success"
                    });
                })
                .catch(error => {
                    this.$vs.notify({
                        title: "Une erreur est survenue",
                        text: error.message,
                        iconPack: "feather",
                        icon: "icon-alert-circle",
                        color: "danger"
                    });
                })
                .finally(() => {
                    this.$vs.loading.close();
                });
            this.handleClose();
        },
        handleClose() {
            //   let test = this.calendarEvents;

            //this.refresh();
            this.activeAddPrompt = false;
        },
        refreshDataUsers() {
            this.filters.user = null;
        },
        refreshDataCalendar() {
            // get user work hours
            this.getBusinessHours();
            this.fetchHours();
        },
        getBusinessHours() {
            this.$store
                .dispatch("userManagement/fetchItem", this.filters.user.id)
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
                                        daysOfWeek: this.getDayNumber(wH.day),
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
                                        daysOfWeek: this.getDayNumber(wH.day),
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
                                if (maxHour === null || maxHour < bH.endTime) {
                                    maxHour = bH.endTime;
                                }
                            });

                            // Add h hours before and after
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
                        this.minTime = "00:00";
                        this.maxTime = "24:00";
                        this.businessHours = false;
                    }
                });
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
        }
    },
    mounted() {},
    created() {
        if (!moduleHourManagement.isRegistered) {
            this.$store.registerModule("hoursManagement", moduleHourManagement);
            moduleHourManagement.isRegistered = true;
        }

        if (!moduleCompanyManagement.isRegistered) {
            this.$store.registerModule(
                "companyManagement",
                moduleCompanyManagement
            );
            moduleCompanyManagement.isRegistered = true;
        }

        if (!moduleProjectManagement.isRegistered) {
            this.$store.registerModule(
                "projectManagement",
                moduleProjectManagement
            );
            moduleProjectManagement.isRegistered = true;
        }

        if (!moduleUnavailabilityManagement.isRegistered) {
            this.$store.registerModule(
                "unavailabilityManagement",
                moduleUnavailabilityManagement
            );
            moduleUnavailabilityManagement.isRegistered = true;
        }

        if (!moduleUserManagement.isRegistered) {
            this.$store.registerModule("userManagement", moduleUserManagement);
            moduleUserManagement.isRegistered = true;
        }

        if (this.authorizedTo("read", "companies")) {
            this.$store.dispatch("companyManagement/fetchItems").catch(err => {
                console.error(err);
            });
        }
        if (this.authorizedTo("read", "users")) {
            this.$store.dispatch("userManagement/fetchItems").catch(err => {
                console.error(err);
            });
        }

        if (!this.isAdmin) {
            this.filters.user = this.$store.state.AppActiveUser;
            this.filters.company = this.filters.user.company;
            this.getBusinessHours();
        }
    },
    beforeDestroy() {
        moduleHourManagement.isRegistered = false;
        moduleProjectManagement.isRegistered = false;
        moduleCompanyManagement.isRegistered = false;
        moduleUserManagement.isRegistered = false;
        moduleUnavailabilityManagement.isRegistered = false;
        this.$store.unregisterModule("hoursManagement");
        this.$store.unregisterModule("projectManagement");
        this.$store.unregisterModule("companyManagement");
        this.$store.unregisterModule("userManagement");
        this.$store.unregisterModule("unavailabilityManagement");
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

.disabled {
    pointer-events: none;
    cursor: not-allowed;
    color: #bfcbd9;
    border-color: #d1dbe5;
    opacity: 0.7;
}
.disabled:hover {
    cursor: not-allowed;
}
</style>
