<!-- =========================================================================================
  File Name: UserEditTabSocial.vue
  Description: User Edit Social Tab content
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
========================================================================================== -->

<template>
  <div>
    <ul class="vx-timeline">
      <li v-for="day in days_of_week" :key="day">
        <div class="timeline-icon" :class="`bg-primary`">
          <feather-icon icon="CalendarIcon" svgClasses="text-white stroke-current w-5 h-5" />
        </div>
        <vs-row class="timeline-info flex flex-row" vs-align="center" vs-type="flex" vs-w="12">
          <vs-col vs-w="1" vs-sm="12" vs-xs="12" class="my-3">
            <vs-checkbox v-model="data_local.work_hours[day].is_active">
              <span class="font-semibold ml-3">{{day}}</span>
            </vs-checkbox>
          </vs-col>
          <vs-col vs-w="1" vs-sm="5" vs-xs="12" class="px-3">
            <flat-pickr
              :config="configTimePicker(9)"
              class="w-full"
              v-model="data_local.work_hours[day].morning_starts_at"
            />
          </vs-col>
          <vs-col vs-w="1" vs-sm="2" vs-xs="12" class="px-3 my-3">
            <div class="text-center">à</div>
          </vs-col>
          <vs-col vs-w="1" vs-sm="5" vs-xs="12" class="px-3">
            <flat-pickr
              :config="configTimePicker()"
              class="w-full"
              v-model="data_local.work_hours[day].morning_ends_at"
            />
          </vs-col>
          <vs-col vs-w="1" vs-sm="12" vs-xs="12" class="px-3 my-3">
            <div class="text-center">et de</div>
          </vs-col>
          <vs-col vs-w="1" vs-sm="5" vs-xs="12" class="px-3">
            <flat-pickr
              :config="configTimePicker(13)"
              class="w-full"
              v-model="data_local.work_hours[day].afternoon_starts_at"
            />
          </vs-col>
          <vs-col vs-w="1" vs-sm="2" vs-xs="12" class="px-3 my-3">
            <div class="text-center">à</div>
          </vs-col>
          <vs-col vs-w="1" vs-sm="5" vs-xs="12" class="px-3">
            <flat-pickr
              :config="configTimePicker(17)"
              class="w-full"
              v-model="data_local.work_hours[day].afternoon_ends_at"
            />
          </vs-col>
        </vs-row>
      </li>
    </ul>

    <vs-table
      ref="table"
      multiple
      v-model="selected"
      pagination
      search
      :data="data_local.indisponibilities || []"
    >
      <div slot="header" class="flex flex-wrap-reverse items-center flex-grow justify-between">
        <div class="flex flex-wrap-reverse items-center data-list-btn-container">
          <!-- ACTION - DROPDOWN -->
          <vs-dropdown vs-trigger-click class="dd-actions cursor-pointer mr-4 mb-4">
            <div
              class="p-4 shadow-drop rounded-lg d-theme-dark-bg cursor-pointer flex items-center justify-center text-lg font-medium w-32 w-full"
            >
              <span class="mr-2">Actions</span>
              <feather-icon icon="ChevronDownIcon" svgClasses="h-4 w-4" />
            </div>

            <vs-dropdown-menu>
              <vs-dropdown-item>
                <span class="flex items-center">
                  <feather-icon icon="TrashIcon" svgClasses="h-4 w-4" class="mr-2" />
                  <span>Supprimer</span>
                </span>
              </vs-dropdown-item>
            </vs-dropdown-menu>
          </vs-dropdown>

          <!-- ADD NEW -->
          <vs-button type="border" class="p-3 mb-4 mr-4" @click="addNewData">
            <feather-icon icon="PlusIcon" svgClasses="h-4 w-4" />
            <span class="ml-2 text-base text-primary">Ajouter une indisponibilité</span>
          </vs-button>
        </div>
      </div>

      <template slot="thead">
        <vs-th sort-key="start">Début</vs-th>
        <vs-th sort-key="end">Fin</vs-th>
        <vs-th sort-key="reason">Motif</vs-th>
        <vs-th>Action</vs-th>
      </template>

      <template slot-scope="{data}">
        <tbody>
          <vs-tr :data="tr" :key="indextr" v-for="(tr, indextr) in data">
            <vs-td>
              <p>{{ tr.start_at }}</p>
            </vs-td>

            <vs-td>
              <p>{{ tr.start_at }} à {{ tr.end_at }}</p>
            </vs-td>

            <vs-td>
              <p class="font-medium truncate">${{ tr.reason }}</p>
            </vs-td>

            <vs-td class="whitespace-no-wrap">
              <feather-icon
                icon="EditIcon"
                svgClasses="w-5 h-5 hover:text-primary stroke-current"
                @click.stop="editData(tr)"
              />
              <feather-icon
                icon="TrashIcon"
                svgClasses="w-5 h-5 hover:text-danger stroke-current"
                class="ml-2"
                @click.stop="deleteData(tr.id)"
              />
            </vs-td>
          </vs-tr>
        </tbody>
      </template>
    </vs-table>

    <!-- Save & Reset Button -->
    <div class="vx-row">
      <div class="vx-col w-full">
        <div class="mt-8 flex flex-wrap items-center justify-end">
          <vs-button
            class="ml-auto mt-2"
            @click="save_changes"
            :disabled="!validateForm"
          >Sauvegarder</vs-button>
          <vs-button
            class="ml-4 mt-2"
            type="border"
            color="warning"
            @click="reset_data"
          >Réinitialiser</vs-button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Moment from "moment";
import flatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import { French as FrenchLocale } from "flatpickr/dist/l10n/fr.js";

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
    flatPickr
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
