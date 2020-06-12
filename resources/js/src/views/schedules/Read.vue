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
    />
  </div>
</template>

<script>
import vSelect from "vue-select";

import FullCalendar from "@fullcalendar/vue";
import dayGridPlugin from "@fullcalendar/daygrid";
import timeGridPlugin from "@fullcalendar/timegrid";
import interactionPlugin from "@fullcalendar/interaction";

// Store Module
import moduleScheduleManagement from "@/store/schedule-management/moduleScheduleManagement.js";
//import moduleTaskManagement from "@/store/task-management/moduleTaskManagement.js";

// must manually include stylesheets for each plugin
import "@fullcalendar/core/main.css";
import "@fullcalendar/daygrid/main.css";
import "@fullcalendar/timegrid/main.css";

export default {
  components: {
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
      calendarEvents: [
        // initial event data
        { id: 0, title: "Event One", start: new Date() },
        {
          id: 1,
          title: "Event Two",
          start: "2020-06-07T14:00:00",
          end: "2020-06-07T18:45:30",
          label: "test label"
        }
      ],
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
  computed: {},
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
      console.log("Click sur un event");
    }
  },
  mounted() {
    console.log("mounted");

    this.$store
      .dispatch("taskManagement/fetchItem", $_GET("id"))
      .then(data => {
        console.log(["data_1", data]);
        this.showDeleteSuccess();
      })
      .catch(err => {
        console.error(err);
      });
  },
  created() {
    if (!moduleScheduleManagement.isRegistered) {
      this.$store.registerModule(
        "ScheduleManagement",
        moduleScheduleManagement
      );
      moduleScheduleManagement.isRegistered = true;
    }
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
