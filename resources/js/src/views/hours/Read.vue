<template>
  <div>
    <router-link :to="'/hours'" class="btnBack flex cursor-pointer text-inherit hover:text-primary pt-3 mb-3">
      <feather-icon class="'h-5 w-5" icon="ArrowLeftIcon"></feather-icon>
      <span class="ml-2"> Retour aux heures </span>
    </router-link>

    <div class="vx-card w-full p-6">

      <add-form
        :activeAddPrompt="this.activeAddPrompt"
        :clickDate="this.dateData"
        :hours_list="this.hoursData"
        :handleClose="handleClose"
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
        :events="calendarEvents"
        @dateClick="handleDateClick"
        @eventClick="handleEventClick"
      />
      <edit-form
        :reload="calendarEvents"
        :itemId="itemIdToEdit"
        v-if="itemIdToEdit && authorizedToEdit "
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

// Component
import EditForm from "./EditForm.vue";
import AddForm from "./AddForm.vue";

// must manually include stylesheets for each plugin
import "@fullcalendar/core/main.css";
import "@fullcalendar/daygrid/main.css";
import "@fullcalendar/timegrid/main.css";

var model = "schedule";
var modelPlurial = "schedules";
var modelTitle = "Plannings";

export default {
  components: {
    FullCalendar, 
    AddForm,
    EditForm
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
      taskBundle: null,
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
      return this.$store.state.hoursManagement.hour.id || -1;
    },
    calendarEvents() {
      return this.$store.state.hoursManagement ? this.$store.state.hoursManagement.hoursCalendar : null;
    },
    authorizedToEdit() {
      return (
        this.$store.getters.userHasPermissionTo(`edit ${modelPlurial}`) > -1
      );
    },
    hoursData() {
      return this.$store.state.hoursManagement ? this.$store.state.hoursManagement.hours : null;
    },
  },
  methods: {
    refresh() {
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
    // handleEventDrop(arg) {
    //   var itemTemp = this.calendarEvents.find(
    //     e => e.id.toString() === arg.event.id
    //   );
    //   //Parse new item to update task

    //   var itemToSave = {
    //     id: itemTemp.id,
    //     name: itemTemp.title,
    //     date: moment(arg.event.start).format("YYYY-MM-DD HH:mm:ss"),
    //     estimated_time: itemTemp.estimated_time,
    //     order: itemTemp.order,
    //     description: itemTemp.description,
    //     time_spent: itemTemp.time_spent,
    //     workarea_id: itemTemp.workarea_id,
    //     user_id: itemTemp.user_id,
    //     project_id: itemTemp.project_id,
    //     status: itemTemp.status,
    //     from: "schedule"
    //   };

    //   this.$store
    //     .dispatch("taskManagement/updateItem", itemToSave)
    //     .then(data => {
    //       if (data && data.status === 200) {
    //         //this.refresh();
    //       } else {
    //       }
    //     })
    //     .catch(err => {
    //       console.error(err);
    //     });
    // },
    // handleEventResize(arg) {
    //   var itemTemp = this.calendarEvents.find(
    //     e => e.id.toString() === arg.event.id
    //   );

    //   //Parse new item to update task

    //   var start = moment(arg.event.start);
    //   var end = moment(arg.event.end);

    //   var itemToSave = {
    //     id: itemTemp.id,
    //     name: itemTemp.title,
    //     date: moment(arg.event.start).format("YYYY-MM-DD HH:mm:ss"),
    //     estimated_time: end.diff(start, "hours"),
    //     order: itemTemp.order,
    //     description: itemTemp.description,
    //     time_spent: itemTemp.time_spent,
    //     workarea_id: itemTemp.workarea_id,
    //     user_id: itemTemp.user_id,
    //     project_id: itemTemp.project_id,
    //     status: itemTemp.status,
    //     from: "schedule"
    //   };

    //   this.$store
    //     .dispatch("taskManagement/updateItem", itemToSave)
    //     .then(data => {
    //       if (data && data.status === 200) {
    //         //this.refresh();
    //       } else {
    //       }
    //     })
    //     .catch(err => {
    //       console.error(err);
    //     });
    // },
    handleClose() {
      this.calendarEvents = []
      let test = this.calendarEvents;

      //this.refresh();
      (this.activeAddPrompt = false), (this.dateData = {});
    }
  },
  mounted() {
    if (this.$route.query.type === "projects") {
      let project = this.$store.state.projectManagement.projects.find(
        p => p.id === parseInt(this.$route.query.id, 10)
      );
      this.scheduleTitle = "Planning du projet : " + project.name;
    } else if (this.$route.query.type === "users") {
      let user = this.$store.state.userManagement.users.find(
        u => u.id === parseInt(this.$route.query.id, 10)
      );
      this.scheduleTitle =
        "Planning de l'utilisateur : " + user.firstname + " " + user.lastname;
    } else if (this.$route.query.type === "workarea") {
      let workarea = this.$store.state.workareaManagement.workareas.find(
        w => w.id === parseInt(this.$route.query.id, 10)
      );
      this.scheduleTitle = "Planning de l'îlot : " + workarea.name;
    }
  },
  created() {

    if (!moduleHourManagement.isRegistered) {
      this.$store.registerModule("hoursManagement", moduleHourManagement);
      moduleHourManagement.isRegistered = true;
    }

    if (!moduleProjectManagement.isRegistered) {
      this.$store.registerModule("projectManagement", moduleProjectManagement);
      moduleProjectManagement.isRegistered = true;
    }
    if (!moduleUserManagement.isRegistered) {
      this.$store.registerModule("userManagement", moduleUserManagement);
      moduleUserManagement.isRegistered = true;
    }
    this.$store.dispatch("hoursManagement/fetchItems").catch(err => {
      this.manageErrors(err);
    });

    this.$store.dispatch("projectManagement/fetchItems");
    this.$store.dispatch("userManagement/fetchItems");
    
  },
  updated() {
    // if (this.$route.query.type === "projects") {
    //   console.log("je passe");
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
    //       .dispatch("taskManagement/fetchItemsByBundle", id_bundle)
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
    moduleHourManagement.isRegistered = false;
    moduleProjectManagement.isRegistered = false;
    moduleUserManagement.isRegistered = false;
    this.$store.unregisterModule("hoursManagement");
    this.$store.unregisterModule("projectManagement");
    this.$store.unregisterModule("userManagement");
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
