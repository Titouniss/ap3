<template>
  <div class="p-3 mb-4 mr-4">
    <vs-button @click="activePrompt = true" class="w-full">Saisie des temps</vs-button>
    <vs-prompt
      title="Saisies des temps"
      accept-text="Ajouter"
      cancel-text="Annuler"
      button-cancel="border"
      @cancel="clearFields"
      @accept="addItem"
      @close="clearFields"
      :is-valid="validateForm"
      :active.sync="showPrompt"
    >
      <div>
        <form>
          <div class="vx-row">
            <div class="vx-col w-full">
              <v-select
                v-validate="'required'"
                name="project_id"
                label="name"
                :multiple="false"
                v-model="itemLocal.project_id"
                :reduce="name => name.id"
                class="w-full"
                autocomplete
                :options="projectsData"
              >
                <template #header>
                  <div style="opacity: .8 font-size: .60rem">Projet</div>
                </template>
                <template #option="project">
                  <span>{{`${project.name}`}}</span>
                </template>
              </v-select>
              <span
                class="text-danger text-sm"
                v-show="errors.has('project_id')"
              >{{ errors.first('project_id') }}</span>

              <v-select
                v-validate="'required'"
                name="user_id"
                label="lastname"
                :multiple="false"
                v-model="itemLocal.user_id"
                :reduce="lastname => lastname.id"
                class="w-full"
                autocomplete
                :options="usersData"
              >
                <template #header>
                  <div style="opacity: .8 font-size: .60rem">Utilisateur</div>
                </template>
                <template #option="user">
                  <span>{{`${user.firstname} ${user.lastname}`}}</span>
                </template>
              </v-select>
              <span
                class="text-danger text-sm"
                v-show="errors.has('user_id')"
              >{{ errors.first('user_id') }}</span>

              <p class="mt-5">Date</p>
              <flat-pickr
                v-validate="'required'"
                name="startDate"
                :config="configDatePicker()"
                class="w-full"
                v-model="itemLocal.date"
                :color="!errors.has('startDate') ? 'success' : 'danger'"
              />
              <span
                class="text-danger text-sm"
                v-show="errors.has('startDate')"
              >{{ errors.first('startDate') }}</span>

              <div class="vx-row">
                <div class="vx-col flex-1">
                  <p class="mt-5">Heure de début</p>
                  <flat-pickr
                    v-validate="'required'"
                    name="startHour"
                    :config="configHourPicker()"
                    class="w-full"
                    v-model="itemLocal.startHour"
                    :color="!errors.has('startHour') ? 'success' : 'danger'"
                  />
                  <span
                    class="text-danger text-sm"
                    v-show="errors.has('startHour')"
                  >{{ errors.first('startHour') }}</span>
                </div>
                <div class="vx-col flex-1">
                  <p class="mt-5">Heure de fin</p>
                  <flat-pickr
                    v-validate="'required'"
                    name="endHour"
                    :config="configHourPicker()"
                    class="w-full"
                    v-model="itemLocal.endHour"
                    :color="!errors.has('endHour') ? 'success' : 'danger'"
                  />
                  <span
                    class="text-danger text-sm"
                    v-show="errors.has('endHour')"
                  >{{ errors.first('endHour') }}</span>
                </div>
              </div>

              <p class="mt-5">Description</p>
              <vs-textarea
                class="w-full mt-4"
                rows="5"
                label="Description"
                v-model="itemLocal.description"
                name="description"
                v-validate="'max:1500'"
              />

            </div>
          </div>
        </form>
      </div>
    </vs-prompt>
  </div>
</template>

<script>
// Store Module
import moduleScheduleManagement from "@/store/schedule-management/moduleScheduleManagement.js";

import moment from "moment";
import vSelect from "vue-select";

// register custom messages
import { Validator } from "vee-validate";
import errorMessage from "./errorValidForm";
Validator.localize("fr", errorMessage);

var model = "tâche";
var modelPlurial = "tâches";

// FlatPickr
import flatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import { French as FrenchLocale } from "flatpickr/dist/l10n/fr.js";

export default {
  props: {
    handleClose: {
      type: Function,
      required: true
    },
    activeAddPrompt: {
      type: Boolean,
      required: true
    },
    clickDate: {
      type: Object,
      required: true
    }
  },
  components: {
    flatPickr,
    vSelect
  },
  data() {
    const user = this.$store.state.AppActiveUser;

    return {
      activePrompt: false,
      itemLocal: {
        startHour: "",
        endHour: "",
        date: "",
        description: "",
        project_id: null,
        user_id: user.id,
      },
      configDatePicker: () => ({
        disableMobile: "true",
        enableTime: false,
        locale: FrenchLocale,
        dateFormat: "Y-m-d",
        altFormat: "j F Y",
        altInput: true
      }),
      configHourPicker: () => ({
        disableMobile: "true",
        enableTime: true,
        locale: FrenchLocale,
         noCalendar: true,
        dateFormat: "H:i",
        altFormat: "H:i",
        altInput: true
      })
    };
  },
  computed: {
    showPrompt: {
      get() {
        if (this.activePrompt || this.activeAddPrompt) {
          if (this.activeAddPrompt) {
              let dateMoment = moment(this.clickDate.dateStr)
              let hour = dateMoment.format('HH:mm:ss')
              let date = dateMoment.format('YYYY-MM-DD')

              this.itemLocal.startHour = hour
              this.itemLocal.date = date
          }
          return true;
        } else {
          return false;
        }
      },
      set(value) {
        return value;
      }
    },
    validateForm() {
      return (
        !this.errors.any() &&
        this.itemLocal.project_id !== "" &&
        this.itemLocal.user_id !== "" &&
        this.itemLocal.date !== "" &&
        this.itemLocal.startHour !== "" &&
        this.itemLocal.endHour !== ""
      );
    },
    projectsData() {
      return this.$store.state.projectManagement.projects;
    },
    usersData() {
      return this.$store.state.userManagement.users;
    },
  },
  methods: {
    clearFields() {
      const user = this.$store.state.AppActiveUser;
      this.itemLocal = {
        date: "",
        startHour: "",
        endHour: "",
        description: "",
        project_id: null,
        user_id: user.id,
      };
      (this.activePrompt = false), this.handleClose();
    },
    addItem() {
      
      this.$validator.validateAll().then(result => {
        if (result) {

          this.itemLocal.start_at = this.itemLocal.date + " " + this.itemLocal.startHour
          this.itemLocal.end_at = this.itemLocal.date + " " + this.itemLocal.endHour

          this.$store.dispatch("hoursManagement/addItem", this.itemLocal)
          .then((response) => {

            if(response.data.success){
              this.$vs.loading.close();
              this.$vs.notify({
                title: "Ajout d'un horaire",
                text: `Horaire ajouté avec succès`,
                iconPack: "feather",
                icon: "icon-alert-circle",
                color: "success"
              });
            }
            else{

              this.$vs.loading.close();
              this.$vs.notify({
                title: "Une erreur est survenue",
                text: response.data.error,
                iconPack: "feather",
                icon: "icon-alert-circle",
                color: "danger"
              });
            }

          })
          .catch(error => {
            this.$vs.loading.close();
            this.$vs.notify({
              title: "Une erreur est survenue",
              text: error.message,
              iconPack: "feather",
              icon: "icon-alert-circle",
              color: "danger"
            });
          });
          this.clearFields();
        }
      });
      this.handleClose()
    }
  },
  created() {}
};
</script>
