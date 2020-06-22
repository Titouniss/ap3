<template>
  <div class="vx-card w-full p-6">
    <h2 class="mb-4 color-primary">{{scheduleTitle}}</h2>
    <!-- <div>
      <button @click="toggleWeekends">toggle weekends</button>
      <button @click="gotoPast">go to a date in the past</button>
      (also, click a date/time to add an event)
    </div>-->
    <add-form
      :activeAddPrompt="this.activeAddPrompt"
      :handleClose="handleClose"
      :dateData="this.dateData"
      :project_data="project_data"
      :tasks_list="this.tasksEvent"
      :customTask="false"
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
      @eventDrop="handleEventDrop"
      @dateClick="handleDateClick"
      @eventClick="handleEventClick"
      @eventResize="handleEventChange"
    />
    <edit-form
      :reload="calendarEvents"
      :itemId="itemIdToEdit"
      v-if="itemIdToEdit && authorizedToEdit "
    />
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
import moduleScheduleManagement from "@/store/schedule-management/moduleScheduleManagement.js";
import moduleTaskManagement from "@/store/task-management/moduleTaskManagement.js";
import moduleSkillManagement from "@/store/skill-management/moduleSkillManagement.js";

// Component
import EditForm from "./EditForm.vue";
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
    FullCalendar // make the <FullCalendar> tag available
  },
  data: function() {
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
      calendarWeekends: true,
      customButtons: {
        AddEventBtn: {
          text: "custom!",
          click: function() {
            alert("clicked the custom button!");
          }
        }
      }
    };
  },
  computed: {
    itemIdToEdit() {
      return this.$store.state.scheduleManagement.event.id || -1;
    },
    calendarEvents() {
      // Get all task and parse to show
      var eventsParse = [];
      if (this.$route.query.type === "projects") {
        if (this.tasksEvent !== []) {
          this.tasksEvent.forEach(t => {
            eventsParse.push({
              id: t.id,
              title: t.name,
              start: t.date,
              estimated_time: t.estimated_time,
              order: t.order,
              description: t.description,
              time_spent: t.time_spent,
              workarea_id: t.workarea_id,
              status: t.status,
              end: moment(t.date)
                .add(t.estimated_time, "hour")
                .format("YYYY-MM-DD HH:mm:ss")
            });
          });
        }
      } else if (this.$route.query.type === "users") {
      } else if (this.$route.query.type === "workarea") {
        if (this.tasksEvent !== []) {
          console.log(["this.tasksEvent", this.tasksEvent]);
          console.log(["this.$route.query.id", this.$route.query.id]);
          this.tasksEvent.forEach(t => {
            if (
              t.workarea_id.toString() === this.$route.query.id ||
              t.workarea_id === this.$route.query.id
            ) {
              eventsParse.push({
                id: t.id,
                title: t.name,
                start: t.date,
                estimated_time: t.estimated_time,
                order: t.order,
                description: t.description,
                time_spent: t.time_spent,
                workarea_id: t.workarea_id,
                status: t.status,
                end: moment(t.date)
                  .add(t.estimated_time, "hour")
                  .format("YYYY-MM-DD HH:mm:ss")
              });
            }
          });
        }
      }

      this.$store
        .dispatch("scheduleManagement/addEvents", eventsParse)
        .catch(err => {
          this.manageErrors(err);
        });
      console.log(["eventsParse", eventsParse]);

      return eventsParse;
    },
    authorizedToEdit() {
      return (
        this.$store.getters.userHasPermissionTo(`edit ${modelPlurial}`) > -1
      );
    },
    tasksEvent() {
      return this.$store.state.taskManagement
        ? this.$store.state.taskManagement.tasks
        : [];
    },
    project_data() {
      var project_data = this.$store.state.projectManagement.projects.find(
        p => (p.id = this.$route.query.id)
      );
      return project_data;
    },
    user_data() {
      var user_data = this.$store.state.userManagement.users.find(
        u => (u.id = this.$route.query.id)
      );
      return user_data;
    },
    workarea_data() {
      var workarea_data = this.$store.state.workareaManagement.workareas.find(
        w => (w.id = this.$route.query.id)
      );
      return workarea_data;
    }
  },
  methods: {
    refresh() {
      // if (this.$route.query.type === "projects") {
      //   console.log("Planning d'un projet");
      //   this.$store
      //     .dispatch("taskManagement/fetchItemsByBundle", this.$route.query.id)
      //     .catch(err => {
      //       this.manageErrors(err);
      //     });
      // }
    },
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
      console.log(["dateData", this.dateData]);
    },
    handleEventClick(arg) {
      var targetEvent = this.calendarEvents.find(
        event => event.id.toString() === arg.event.id
      );

      this.$store
        .dispatch("scheduleManagement/editEvent", targetEvent)
        .catch(err => {
          console.error(err);
        });
    },
    handleEventDrop(arg) {
      var itemTemp = this.calendarEvents.find(
        e => e.id.toString() === arg.event.id
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
        status: itemTemp.status,
        from: "schedule"
      };

      this.$store
        .dispatch("taskManagement/updateItem", itemToSave)
        .then(data => {
          console.log(["data", data]);
          if (data && data.status === 200) {
            //this.refresh();
          } else {
          }
        })
        .catch(err => {
          console.error(err);
        });
    },
    handleEventResize(arg) {
      var itemTemp = this.calendarEvents.find(
        e => e.id.toString() === arg.event.id
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
        status: itemTemp.status,
        from: "schedule"
      };

      this.$store
        .dispatch("taskManagement/updateItem", itemToSave)
        .then(data => {
          console.log(["data", data]);
          if (data && data.status === 200) {
            //this.refresh();
          } else {
          }
        })
        .catch(err => {
          console.error(err);
        });
    },
    handleClose() {
      this.refresh();
      (this.activeAddPrompt = false), (this.dateData = {});
    }
  },
  mounted() {
    console.log(["state", this.$store.state]);

    if (this.$route.query.type === "projects") {
      this.scheduleTitle = "Planning du projet : " + this.project_data.name;
    } else if (this.$route.query.type === "users") {
      this.scheduleTitle =
        "Planning de l'utilisateur : " +
        this.user_data.firstname +
        " " +
        this.user_data.lastname;
    } else if (this.$route.query.type === "workarea") {
      this.scheduleTitle = "Planning de l'îlot : " + this.workarea_data.name;
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
    if (!moduleSkillManagement.isRegistered) {
      this.$store.registerModule("skillManagement", moduleSkillManagement);
      moduleSkillManagement.isRegistered = true;
    }

    if (this.$route.query.type === "projects") {
      this.$store
        .dispatch("taskManagement/fetchItemsByBundle", this.$route.query.id)
        .catch(err => {
          this.manageErrors(err);
        });
    } else if (this.$route.query.type === "users") {
    } else if (this.$route.query.type === "workarea") {
      this.$store.dispatch("taskManagement/fetchItems").catch(err => {
        this.manageErrors(err);
      });
    }
    this.$store.dispatch("skillManagement/fetchItems").catch(err => {
      this.manageErrors(err);
    });
  },
  beforeDestroy() {
    moduleScheduleManagement.isRegistered = false;
    moduleTaskManagement.isRegistered = false;
    moduleSkillManagement.isRegistered = false;
    this.$store.unregisterModule("scheduleManagement");
    this.$store.unregisterModule("taskManagement");
    this.$store.unregisterModule("skillManagement");
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
</style>
