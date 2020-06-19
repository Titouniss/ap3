<template>
  <div>
    <ul class="vx-timeline">
      <li v-for="day in days_of_week" :key="day">
        <div class="timeline-icon" :class="`bg-primary`">
          <feather-icon icon="CalendarIcon" svgClasses="text-white stroke-current w-5 h-5" />
        </div>
        <vs-row class="timeline-info" vs-justify="center" vs-type="flex" vs-w="12">
          <vs-divider position="left">
            <vs-checkbox v-model="data_local.work_hours[day].is_active">
              <span class="font-semibold ml-3">{{day}}</span>
            </vs-checkbox>
          </vs-divider>
          <vs-col vs-w="1" vs-sm="5" vs-xs="12">
            <flat-pickr
              :config="configTimePicker(9)"
              class="w-full"
              v-model="data_local.work_hours[day].morning_starts_at"
            />
          </vs-col>
          <vs-col vs-w="1" vs-sm="2" vs-xs="12" class="px-3 my-3">
            <div class="text-center">à</div>
          </vs-col>
          <vs-col vs-w="1" vs-sm="5" vs-xs="12">
            <flat-pickr
              :config="configTimePicker(12)"
              class="w-full"
              v-model="data_local.work_hours[day].morning_ends_at"
            />
          </vs-col>
          <vs-col vs-w="1" vs-sm="12" vs-xs="12" class="px-3 my-3">
            <div class="text-center">et de</div>
          </vs-col>
          <vs-col vs-w="1" vs-sm="5" vs-xs="12">
            <flat-pickr
              :config="configTimePicker(13)"
              class="w-full"
              v-model="data_local.work_hours[day].afternoon_starts_at"
            />
          </vs-col>
          <vs-col vs-w="1" vs-sm="2" vs-xs="12" class="px-3 my-3">
            <div class="text-center">à</div>
          </vs-col>
          <vs-col vs-w="1" vs-sm="5" vs-xs="12">
            <flat-pickr
              :config="configTimePicker(17)"
              class="w-full"
              v-model="data_local.work_hours[day].afternoon_ends_at"
            />
          </vs-col>
        </vs-row>
      </li>
    </ul>

    <!-- Save & Reset Button -->
    <div class="vx-row my-5">
      <div class="vx-col w-full">
        <div class="flex flex-wrap items-center justify-end">
          <vs-button class="ml-auto" @click="save_changes" :disabled="!validateForm">Sauvegarder</vs-button>
          <vs-button class="ml-4" type="border" color="warning" @click="reset_data">Réinitialiser</vs-button>
        </div>
      </div>
    </div>

    <vs-divider />

    <unavailabilities-index />
  </div>
</template>

<script>
import Moment from "moment";
import flatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import { French as FrenchLocale } from "flatpickr/dist/l10n/fr.js";
import UnavailabilitiesIndex from "../../unavailabilities/Index.vue";

const daysOfweek = [
  "Lundi",
  "Mardi",
  "Mercredi",
  "Jeudi",
  "Vendredi",
  "Samedi",
  "Dimanche"
];

export default {
  props: {
    data: {
      type: Object,
      required: true
    }
  },
  data() {
    return {
      selected: [], // used by checkbox to delete
      configTimePicker: (hour = 12) => ({
        disableMobile: "true",
        enableTime: true,
        locale: FrenchLocale,
        noCalendar: true,
        defaultHour: hour
      }),
      data_local: this.getDataWithWorkHours(),
      days_of_week: daysOfweek
    };
  },
  components: {
    flatPickr,
    UnavailabilitiesIndex
  },
  computed: {
    validateForm() {
      return !this.errors.any();
    }
  },
  methods: {
    addNewData() {
      return;
    },
    save_changes() {
      if (!this.validateForm) return;

      this.$store
        .dispatch("userManagement/updateWorkHoursItem", this.data_local)
        .then(response => {
          this.$vs.loading.close();
          this.$vs.notify({
            title: "Modification du compte",
            text: "Vos données ont étés modifiées avec succès",
            iconPack: "feather",
            icon: "icon-alert-circle",
            color: "success"
          });
          console.log(response.data);
        })
        .catch(error => {
          this.$vs.loading.close();
          this.$vs.notify({
            title: "Error",
            text: "Une erreur est survenue, veuillez réessayer plus tard.",
            iconPack: "feather",
            icon: "icon-alert-circle",
            color: "danger"
          });
        });
    },
    reset_data() {
      this.data_local = this.getDataWithWorkHours();
    },
    getDataWithWorkHours() {
      const workHours = {};

      daysOfweek.forEach(d => {
        workHours[d] = {
          is_active: false,
          morning_starts_at: null,
          morning_ends_at: null,
          afternoon_starts_at: null,
          afternoon_ends_at: null
        };
      });

      this.data.work_hours.forEach(h => {
        const label = h.day.charAt(0).toUpperCase() + h.day.slice(1);
        workHours[label].morning_starts_at = h.morning_starts_at;
        workHours[label].morning_ends_at = h.morning_ends_at;
        workHours[label].afternoon_starts_at = h.afternoon_starts_at;
        workHours[label].afternoon_ends_at = h.afternoon_ends_at;
        workHours[label].is_active = h.is_active === 1;
      });

      const newData = JSON.parse(JSON.stringify(this.data));
      newData.work_hours = workHours;
      return newData;
    }
  }
};
</script>

<style lang="scss">
@import "@sass/vuexy/components/vxTimeline.scss";
</style>
