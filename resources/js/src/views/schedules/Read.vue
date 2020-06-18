<template>
  <div class="vx-card w-full p-6">
    <!-- <div>
      <button @click="toggleWeekends">toggle weekends</button>
      <button @click="gotoPast">go to a date in the past</button>
      (also, click a date/time to add an event)
    </div>-->
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
      :plugins="calendarPlugins"
      :weekends="calendarWeekends"
      :events="calendarEvents"
      @dateClick="handleDateClick"
      @eventClick="handleEventClick"
      @eventDrop="handleEventChange"
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
//import moduleTaskManagement from "@/store/task-management/moduleTaskManagement.js";

// Component
import EditForm from "./EditForm.vue";

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
      return this.$store.state.scheduleManagement.events;
    },
    authorizedToEdit() {
      return (
        this.$store.getters.userHasPermissionTo(`edit ${modelPlurial}`) > -1
      );
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
      if (confirm("Would you like to add an event to " + arg.dateStr + " ?")) {
        this.calendarEvents.push({
          // add new event data
          title: "New Event",
          start: arg.date,
          allDay: arg.allDay
        });
      }
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
    handleEventChange(eventDropInfo) {
      event = eventDropInfo.event;

      // Get task id with eventDropInfo.event.id

      // Get estimated duration
      estimateTime = moment
        .duration(moment(event.start).diff(moment(event.end)))
        .asHours();
    }
  },
  mounted() {},
  created() {
    if (!moduleScheduleManagement.isRegistered) {
      this.$store.registerModule(
        "scheduleManagement",
        moduleScheduleManagement
      );
      moduleScheduleManagement.isRegistered = true;
    }
    this.$store
      .dispatch("scheduleManagement/addEvents", [
        // initial event data
        {
          id: 1,
          title: "Event One",
          start: "2020-06-15T11:00:00",
          end: "2020-06-15T12:45:30",
          label: "test label 1"
        },
        {
          id: 2,
          title: "Event Two",
          start: "2020-06-17T14:00:00",
          end: "2020-06-17T18:45:30",
          label: "test label 2"
        }
      ])
      .catch(err => {
        this.manageErrors(err);
      });
  },
  beforeDestroy() {
    moduleScheduleManagement.isRegistered = false;
    this.$store.unregisterModule("scheduleManagement");
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
