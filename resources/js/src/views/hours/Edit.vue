<template>
  <div id="page-hours-edit">
    <vs-alert color="danger" title="Hours Not Found" :active.sync="hours_not_found">
      <span>Les heures {{ $route.params.id }} est introuvable.</span>
      <span>
        <span>voir</span>
        <router-link :to="{name:'hours'}" class="text-inherit underline">Toutes les heures</router-link>
      </span>
    </vs-alert>
    <vx-card>
      <div slot="no-body" class="tabs-container px-6 pt-6">
        <vs-row vs-justify="center" vs-type="flex" vs-w="12">
          <vs-col vs-w="6" vs-xs="12" class="mt-6 px-6">
            <vs-select
              v-validate="'required'"
              name="project_id"
              label="Projet"
              v-model="data_local.project_id"
              class="w-full"
            >
              <vs-select-item
                :key="index"
                :value="item.id"
                :text="item.name"
                v-for="(item,index) in projects"
              />
            </vs-select>
            <span
              class="text-danger text-sm"
              v-show="errors.has('project_id')"
            >{{ errors.first('project_id') }}</span>
          </vs-col>
          <vs-col vs-w="6" vs-xs="12" class="mt-6 px-6" v-if="authorizedToReadUsers">
            <vs-select
              v-validate="'required'"
              name="user_id"
              label="Utilisateur"
              v-model="data_local.user_id"
              class="w-full"
            >
              <vs-select-item
                :key="index"
                :value="item.id"
                :text="item.firstname + ' ' + item.lastname"
                v-for="(item,index) in users"
              />
            </vs-select>
            <span
              class="text-danger text-sm"
              v-show="errors.has('user_id')"
            >{{ errors.first('user_id') }}</span>
          </vs-col>
        </vs-row>

        <vs-row vs-justify="center" vs-type="flex" vs-w="12">
          <vs-col vs-w="6" vs-xs="12" class="mt-6 px-6">
            <small class="date-label pl-1">Date</small>
            <flat-pickr :config="configDatePicker()" class="w-full" v-model="data_local.date" />
          </vs-col>
          <vs-col vs-w="6" vs-xs="12" class="mt-6 px-6">
            <small class="date-label pl-1">Durée</small>
            <flat-pickr :config="configTimePicker()" class="w-full" v-model="data_local.duration" />
          </vs-col>
        </vs-row>

        <vs-row vs-justify="center" vs-type="flex" vs-w="12">
          <vs-col vs-w="12" class="mt-6 px-6">
            <vs-textarea
              class="w-full mt-4"
              rows="5"
              label="Description"
              v-model="data_local.description"
              name="description"
              v-validate="'max:1500'"
            />
            <span
              class="text-danger text-sm"
              v-show="errors.has('description')"
            >{{ errors.first('description') }}</span>
          </vs-col>
        </vs-row>
      </div>
      <!-- Save & Reset Button -->
      <div class="vx-row">
        <div class="vx-col w-full">
          <div class="mt-8 flex flex-wrap items-center justify-end">
            <vs-button class="ml-auto mt-2" @click="save_changes" :disabled="!validateForm">Modifier</vs-button>
            <vs-button class="ml-4 mt-2" type="border" color="warning" @click="back">Annuler</vs-button>
          </div>
        </div>
      </div>
    </vx-card>
  </div>
</template>

<script>
// Store Module
import moduleHoursManagement from "@/store/hours-management/moduleHoursManagement.js";
import moduleProjectManagement from "@/store/project-management/moduleProjectManagement.js";
import moduleUserManagement from "@/store/user-management/moduleUserManagement.js";

import { Validator } from "vee-validate";
import errorMessage from "./errorValidForm";

import flatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import { French as FrenchLocale } from "flatpickr/dist/l10n/fr.js";

// register custom messages
Validator.localize("fr", errorMessage);
var model = "hours";
var modelPlurial = "hours";
var modelTitle = "Heures";

export default {
  data() {
    const user = this.$store.state.AppActiveUser;
    return {
      data_local: {
        date: null,
        duration: null,
        description: "",
        project_id: null,
        user_id: user.id
      },
      hours_not_found: false,
      configTimePicker: () => ({
        disableMobile: "true",
        enableTime: true,
        locale: FrenchLocale,
        noCalendar: true,
        defaultHour: 0
      }),
      configDatePicker: () => ({
        disableMobile: "true",
        enableTime: false,
        locale: FrenchLocale,
        altFormat: "j F Y",
        altInput: true
      })
    };
  },
  components: {
    flatPickr
  },
  computed: {
    authorizedToReadUsers() {
      return this.$store.getters.userHasPermissionTo(`read users`);
    },
    projects() {
      return this.$store.state.projectManagement.projects;
    },
    users() {
      return this.$store.state.userManagement.users;
    },
    validateForm() {
      return (
        !this.errors.any() &&
        this.data_local.project_id &&
        this.data_local.user_id &&
        this.data_local.date &&
        this.data_local.duration
      );
    }
  },
  methods: {
    fetch_data(id) {
      this.$store
        .dispatch("hoursManagement/fetchItem", id)
        .then(res => {
          this.data_local = res.data.success;
        })
        .catch(err => {
          if (err.response.status === 404) {
            this.hours_not_found = true;
            return;
          }
          console.error(err);
        });
    },
    save_changes() {
      /* eslint-disable */
      if (!this.validateForm) return;
      this.$vs.loading();

      const payload = { ...this.data_local };
      this.$store
        .dispatch("hoursManagement/updateItem", payload)
        .then(() => {
          this.$vs.loading.close();
          this.$vs.notify({
            title: "Modification",
            text: "Heures modifiées avec succès",
            iconPack: "feather",
            icon: "icon-alert-circle",
            color: "success"
          });
          this.$router.push(`/${modelPlurial}`).catch(() => {});
        })
        .catch(error => {
          this.$vs.loading.close();
          this.$vs.notify({
            title: "Echec",
            text: error.message,
            iconPack: "feather",
            icon: "icon-alert-circle",
            color: "danger"
          });
        });
    },
    back() {
      this.$router.push(`/${modelPlurial}`).catch(() => {});
    }
  },
  created() {
    if (!moduleHoursManagement.isRegistered) {
      this.$store.registerModule("hoursManagement", moduleHoursManagement);
      moduleHoursManagement.isRegistered = true;
    }
    if (!moduleProjectManagement.isRegistered) {
      this.$store.registerModule("projectManagement", moduleProjectManagement);
      moduleProjectManagement.isRegistered = true;
    }
    if (!moduleUserManagement.isRegistered) {
      this.$store.registerModule("userManagement", moduleUserManagement);
      moduleUserManagement.isRegistered = true;
    }
    this.fetch_data(this.$route.params.id);
    this.$store.dispatch("projectManagement/fetchItems");
    if (this.authorizedToReadUsers) {
      this.$store.dispatch("userManagement/fetchItems");
    }
  },
  beforeDestroy() {
    moduleHoursManagement.isRegistered = false;
    moduleProjectManagement.isRegistered = false;
    moduleUserManagement.isRegistered = false;
    this.$store.unregisterModule("hoursManagement");
    this.$store.unregisterModule("projectManagement");
    this.$store.unregisterModule("userManagement");
  }
};
</script>
