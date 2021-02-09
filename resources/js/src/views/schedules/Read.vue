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
        :hideProjectInput="this.$route.query.type === 'projects' ? true : false"
        :hideUserInput="this.$route.query.type === 'users' ? true : false"
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
          right: 'title',
        }"
        :views="{
          timeGridWeek: {
            titleFormat: '{dddd DD MMM}, [Semaine] w, YYYY',
          },
        }"
        :buttonText="{
          today: 'Aujourd\'hui',
          month: 'Mois',
          week: 'Semaine',
          day: 'Jour',
          list: 'Liste',
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
      <edit-form
        :itemId="itemIdToEdit"
        :tasks_list="tasksEvent"
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

// Component
import EditForm from "../tasks/EditForm.vue";
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
    AddForm,
    FullCalendar, // make the <FullCalendar> tag available
  },
  data: function () {
    return {
      calendarPlugins: [
        // plugins must be defined in the JS
        dayGridPlugin,
        timeGridPlugin,
        interactionPlugin, // needed for dateClick
        momentPlugin,
      ],
      activeAddPrompt: false,
      dateData: {},
      taskBundle: null,
      calendarWeekends: true,
      customButtons: {
        AddEventBtn: {
          text: "custom!",
          click: function () {
            alert("clicked the custom button!");
          },
        },
      },
      businessHours: false,
      minTime: "05:00",
      maxTime: "24:00",
    };
  },
  computed: {
    itemIdToEdit() {
      return this.$store.state.taskManagement.task.id || 0;
    },
    calendarEvents() {
      // Get all task and parse to show
      var eventsParse = [];
      let minHour = null;
      let maxHour = null;
      if (this.$route.query.type === "projects") {
        if (this.tasksEvent !== []) {
          this.tasksEvent.forEach((t) => {
            t.periods.forEach((p) => {
              let start_period_hour = moment(p.start_time).format("HH:mm");
              let end_period_hour = moment(p.end_time).format("HH:mm");

              minHour == null || start_period_hour < minHour
                ? (minHour = start_period_hour)
                : null;
              maxHour == null || end_period_hour > maxHour
                ? (maxHour = end_period_hour)
                : null;

              eventsParse.push({
                id: t.id,
                title: t.name,
                start: p.start_time,
                estimated_time: t.estimated_time,
                order: t.order,
                description: t.description,
                time_spent: t.time_spent,
                workarea_id: t.workarea_id,
                status: t.status,
                end: p.end_time,
                user_id: t.user_id,
                project_id: parseInt(this.$route.query.id, 10),
                color: t.project.color,
              });
            });
          });
        }

        console.log(minHour);
        console.log(maxHour);
      } else if (this.$route.query.type === "users") {
        if (this.tasksEvent !== []) {
          this.tasksEvent.forEach((t) => {
            // if (
            //   t.user_id !== null &&
            //   (t.user_id.toString() === this.$route.query.id ||
            //     t.user_id === this.$route.query.id)
            // ) {
            // Get project id
            let project_id = null;
            this.$store.state.projectManagement.projects.forEach((p) => {
              p.tasks_bundles.forEach((tb) => {
                if (t.tasks_bundle_id === tb.id) {
                  project_id = tb.project_id;
                }
              });
            });

            t.periods.forEach((p) => {
              let start_period_hour = moment(p.start_time).format("HH:mm");
              let end_period_hour = moment(p.end_time).format("HH:mm");

              minHour == null || start_period_hour < minHour
                ? (minHour = start_period_hour)
                : null;
              maxHour == null || end_period_hour > maxHour
                ? (maxHour = end_period_hour)
                : null;

              eventsParse.push({
                id: t.id,
                title: t.name,
                start: p.start_time,
                estimated_time: t.estimated_time,
                order: t.order,
                description: t.description,
                time_spent: t.time_spent,
                workarea_id: t.workarea_id,
                status: t.status,
                end: p.end_time,
                user_id: t.user_id,
                project_id: project_id,
                color: t.project.color,
              });
            });
            // }
          });
        }
      } else if (this.$route.query.type === "workarea") {
        if (this.tasksEvent !== []) {
          this.tasksEvent.forEach((t) => {
            // if (
            //   t.workarea_id !== null &&
            //   (t.workarea_id.toString() === this.$route.query.id ||
            //     t.workarea_id === this.$route.query.id)
            // ) {
            // Get project id
            let project_id = null;
            this.$store.state.projectManagement.projects.forEach((p) => {
              p.tasks_bundles.forEach((tb) => {
                if (t.tasks_bundle_id === tb.id) {
                  project_id = tb.project_id;
                }
              });
            });

            t.periods.forEach((p) => {
              let start_period_hour = moment(p.start_time).format("HH:mm");
              let end_period_hour = moment(p.end_time).format("HH:mm");

              minHour == null || start_period_hour < minHour
                ? (minHour = start_period_hour)
                : null;
              maxHour == null || end_period_hour > maxHour
                ? (maxHour = end_period_hour)
                : null;

              eventsParse.push({
                id: t.id,
                title: t.name,
                start: p.start_time,
                estimated_time: t.estimated_time,
                order: t.order,
                description: t.description,
                time_spent: t.time_spent,
                workarea_id: t.workarea_id,
                status: t.status,
                end: p.end_time,
                user_id: t.user_id,
                project_id: project_id,
                color: t.project.color,
              });
            });
            // }
          });
        }
      }

      this.minTime =
        minHour && minHour >= "02:00"
          ? moment(minHour, "HH:mm").subtract(2, "hour").format("HH:mm")
          : "00:00";
      this.maxTime =
        maxHour && maxHour <= "22:00"
          ? moment(maxHour, "HH:mm").add(2, "hour").format("HH:mm")
          : "24:00";

      this.$store
        .dispatch("scheduleManagement/addItems", eventsParse)
        .catch((err) => {
          this.manageErrors(err);
        });

      return eventsParse;
    },
    tasksEvent() {
      return this.$store.state.taskManagement
        ? this.$store.state.taskManagement.tasks
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
      var user_data = this.$store.state.userManagement.users.find(
        (u) => u.id === this.$route.query.id
      );
      return user_data;
    },
    workarea_data() {
      var workarea_data = this.$store.state.workareaManagement.workareas.find(
        (w) => w.id === this.$route.query.id
      );
      return workarea_data;
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
          title = "Planning du pôle de produciton : " + this.workarea_data.name;
        }
      }
      return title;
    },
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
        (event) => event.id.toString() === arg.event.id
      );

      this.$store
        .dispatch("taskManagement/editItem", targetEvent)
        .catch((err) => {
          console.error(err);
        });
    },
    handleEventDrop(arg) {
      var itemTemp = this.calendarEvents.find(
        (e) => e.id.toString() === arg.event.id
      );
      //Parse new item to update task

      var itemToSave = {
        id: itemTemp.id,
        name: itemTemp.title,
        date: moment(arg.event.start).format("YYYY-MM-DD HH:mm:ss"),
        estimated_time: itemTemp.estimated_time,
        order: itemTemp.order,
        description: itemTemp.description,
        time_spent: itemTemp.time_spent,
        workarea_id: itemTemp.workarea_id,
        user_id: itemTemp.user_id,
        project_id: itemTemp.project_id,
        status: itemTemp.status,
        from: "schedule",
      };

      this.$store
        .dispatch("taskManagement/updateItem", itemToSave)
        .then((data) => {
          if (data && data.status === 200) {
            //this.refresh();
          } else {
          }
        })
        .catch((err) => {
          console.error(err);
        });
    },
    handleEventResize(arg) {
      var itemTemp = this.calendarEvents.find(
        (e) => e.id.toString() === arg.event.id
      );

      //Parse new item to update task

      var start = moment(arg.event.start);
      var end = moment(arg.event.end);

      var itemToSave = {
        id: itemTemp.id,
        name: itemTemp.title,
        date: moment(arg.event.start).format("YYYY-MM-DD HH:mm:ss"),
        estimated_time: end.diff(start, "hours"),
        order: itemTemp.order,
        description: itemTemp.description,
        time_spent: itemTemp.time_spent,
        workarea_id: itemTemp.workarea_id,
        user_id: itemTemp.user_id,
        project_id: itemTemp.project_id,
        status: itemTemp.status,
        from: "schedule",
      };

      this.$store
        .dispatch("taskManagement/updateItem", itemToSave)
        .then((data) => {
          if (data && data.status === 200) {
            //this.refresh();
          } else {
          }
        })
        .catch((err) => {
          console.error(err);
        });
    },
    handleClose() {
      //this.refresh();
      (this.activeAddPrompt = false), (this.dateData = {});
    },
    authorizedTo(action, model = modelPlurial) {
      return this.$store.getters.userHasPermissionTo(`${action} ${model}`);
    },
    getBusinessHours() {
      if (this.$route.query.type === "users") {
        this.$store
          .dispatch("userManagement/fetchItem", this.$route.query.id)
          .then((data) => {
            let item = data.payload;
            if (item.work_hours.length > 0) {
              let businessHours = [];
              item.work_hours.forEach((wH) => {
                if (wH.is_active === 1) {
                  if (
                    wH.morning_starts_at !== null &&
                    wH.morning_ends_at !== null
                  ) {
                    businessHours.push({
                      daysOfWeek: this.getDayNumber(wH.day),
                      startTime: this.parseWorkHour(wH.morning_starts_at),
                      endTime: this.parseWorkHour(wH.morning_ends_at),
                    });
                  }
                  if (
                    wH.afternoon_starts_at !== null &&
                    wH.afternoon_ends_at !== null
                  ) {
                    businessHours.push({
                      daysOfWeek: this.getDayNumber(wH.day),
                      startTime: this.parseWorkHour(wH.afternoon_starts_at),
                      endTime: this.parseWorkHour(wH.afternoon_ends_at),
                    });
                  }
                }
              });
              if (businessHours !== []) {
                let minHour = null;
                let maxHour = null;
                businessHours.forEach((bH) => {
                  if (minHour === null || minHour > bH.startTime) {
                    minHour = bH.startTime;
                  }
                  if (maxHour === null || maxHour < bH.endTime) {
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
                    ? moment(maxHour, "HH:mm").add(2, "hour").format("HH:mm")
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
  },
  created() {
    if (this.$route.query.type === "users") {
      this.getBusinessHours();
    }

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
      this.$store.registerModule("projectManagement", moduleProjectManagement);
      moduleProjectManagement.isRegistered = true;
    }
    if (!moduleSkillManagement.isRegistered) {
      this.$store.registerModule("skillManagement", moduleSkillManagement);
      moduleSkillManagement.isRegistered = true;
    }
    if (!moduleUserManagement.isRegistered) {
      this.$store.registerModule("userManagement", moduleUserManagement);
      moduleUserManagement.isRegistered = true;
    }

    if (!moduleDocumentManagement.isRegistered) {
      this.$store.registerModule(
        "documentManagement",
        moduleDocumentManagement
      );
      moduleDocumentManagement.isRegistered = true;
    }

    //this.$store.state.taskManagement.tasks = [];

    if (this.$route.query.type === "projects") {
      if (this.authorizedTo("read", "projects")) {
        this.$store.dispatch("projectManagement/fetchItems").catch((err) => {
          console.error(err);
        });
      }
      var id_bundle = null;

      this.$store.state.projectManagement.projects.forEach((p) => {
        p.tasks_bundles.forEach((t) => {
          if (t.project_id === parseInt(this.$route.query.id, 10)) {
            id_bundle = t.id;
          }
        });
      });
      if (id_bundle != null) {
        this.$store
          .dispatch("taskManagement/fetchItems", { tasks_bundle_id: id_bundle })
          .catch((err) => {
            this.manageErrors(err);
          });
      }
    } else if (this.$route.query.type === "users") {
      this.$store
        .dispatch("taskManagement/fetchItems", {
          user_id: this.$route.query.id,
        })
        .catch((err) => {
          this.manageErrors(err);
        });
    } else if (this.$route.query.type === "workarea") {
      this.$store
        .dispatch("taskManagement/fetchItems", {
          workarea: this.$route.query.id,
        })
        .catch((err) => {
          this.manageErrors(err);
        });
    }

    this.$store.dispatch("skillManagement/fetchItems").catch((err) => {
      this.manageErrors(err);
    });
    if (this.authorizedTo("read", "users")) {
      this.$store.dispatch("userManagement/fetchItems").catch((err) => {
        this.manageErrors(err);
      });
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
  },
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
