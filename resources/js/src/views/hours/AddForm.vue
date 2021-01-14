<template>
  <div class="p-3 mb-4 mr-4">
    <vs-button @click="activePrompt = true" class="w-full"
      >Saisie des temps</vs-button
    >
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
        <form autocomplete="off">
          <div class="vx-row">
            <div class="vx-col w-full">
              <v-select
                v-validate="'required'"
                name="project_id"
                label="name"
                :multiple="false"
                v-model="itemLocal.project_id"
                :reduce="(project) => project.id"
                class="w-full"
                autocomplete
                :options="projectsData"
              >
                <template #header>
                  <div style="opacity: .8 font-size: .60rem">Projet</div>
                </template>
              </v-select>
              <span
                class="text-danger text-sm"
                v-show="errors.has('project_id')"
                >{{ errors.first("project_id") }}</span
              >

              <!-- <v-select
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
                                    <div style="opacity: .8 font-size: .60rem">
                                        Utilisateur
                                    </div>
                                </template>
                                <template #option="user">
                                    <span>{{
                                        `${user.firstname} ${user.lastname}`
                                    }}</span>
                                </template>
                            </v-select>
                            <span
                                class="text-danger text-sm"
                                v-show="errors.has('user_id')"
                                >{{ errors.first("user_id") }}</span
                            > -->

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
                >{{ errors.first("startDate") }}</span
              >

              <div class="vx-row">
                <div class="vx-col flex-1">
                  <p class="mt-5">Heure de début</p>
                  <flat-pickr
                    v-validate="'required'"
                    name="startHour"
                    :config="configStartHourPicker"
                    class="w-full"
                    v-model="itemLocal.startHour"
                    :color="!errors.has('startHour') ? 'success' : 'danger'"
                    :onChange="definedMinEndHour()"
                  />
                  <span
                    class="text-danger text-sm"
                    v-show="errors.has('startHour')"
                    >{{ errors.first("startHour") }}</span
                  >
                </div>
                <div class="vx-col flex-1" v-if="itemLocal.startHour !== ''">
                  <p class="mt-5">Heure de fin</p>
                  <flat-pickr
                    v-validate="'required'"
                    name="endHour"
                    :config="configEndHourPicker"
                    class="w-full"
                    v-model="itemLocal.endHour"
                    :color="!errors.has('endHour') ? 'success' : 'danger'"
                  />
                  <span
                    class="text-danger text-sm"
                    v-show="errors.has('endHour')"
                    >{{ errors.first("endHour") }}</span
                  >
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
      required: true,
    },
    activeAddPrompt: {
      type: Boolean,
      required: true,
    },
    clickDate: {
      type: Object,
      required: true,
    },
    user: {
      type: Object,
    },
  },
  components: {
    flatPickr,
    vSelect,
  },
  data() {
    const user = this.user || this.$store.state.AppActiveUser;
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
      company_id: user.company_id,
      updateEndHour: 12,
      endDisable: true,
      configDatePicker: () => ({
        disableMobile: "true",
        enableTime: false,
        locale: FrenchLocale,
        dateFormat: "Y-m-d",
        altFormat: "j F Y",
        altInput: true,
      }),
    };
  },
  watch: {
    user(newUser, oldUser) {
      if (newUser) {
        this.itemLocal.user_id = newUser.id;
        this.company_id = newUser.company_id;
      }
    },
  },
  computed: {
    configStartHourPicker: {
      get() {
        return {
          disableMobile: "true",
          enableTime: true,
          locale: FrenchLocale,
          noCalendar: true,
          dateFormat: "H:i",
          altFormat: "H:i",
          altInput: true,
          maxTime:
            this.itemLocal.endHour.split(":")[1] === "00"
              ? moment(this.itemLocal.endHour, "HH:mm")
                  .subtract(1, "h")
                  .set("m", 55)
                  .format("HH:mm")
              : moment(this.itemLocal.endHour, "HH:mm")
                  .subtract(5, "m")
                  .format("HH:mm"),
        };
      },
      set(value) {
        return value;
      },
    },
    configEndHourPicker: {
      get() {
        return {
          disableMobile: "true",
          enableTime: true,
          locale: FrenchLocale,
          noCalendar: true,
          dateFormat: "H:i",
          altFormat: "H:i",
          altInput: true,
          minTime:
            this.itemLocal.startHour.split(":")[1] === "55"
              ? moment(this.itemLocal.startHour, "HH:mm")
                  .set("m", 0)
                  .add(1, "h")
                  .format("HH:mm")
              : moment(this.itemLocal.startHour, "HH:mm")
                  .add(5, "m")
                  .format("HH:mm"),
          defaultHour: this.updateEndHour,
        };
      },
      set(value) {
        this.updateEndHour = value;
      },
    },
    showPrompt: {
      get() {
        if (this.activePrompt || this.activeAddPrompt) {
          if (this.activeAddPrompt) {
            let dateMoment = moment(this.clickDate.dateStr);
            let hour = dateMoment.format("HH:mm:ss");
            let date = dateMoment.format("YYYY-MM-DD");

            this.itemLocal.startHour = hour;
            this.itemLocal.date = date;
          }
          return true;
        } else {
          return false;
        }
      },
      set(value) {
        return value;
      },
    },
    validateForm() {
      return (
        !this.errors.any() &&
        this.itemLocal.project_id !== null &&
        this.itemLocal.user_id !== "" &&
        this.itemLocal.date !== "" &&
        this.itemLocal.startHour !== "" &&
        this.itemLocal.endHour !== ""
      );
    },
    projectsData() {
      return this.$store.state.projectManagement.projects
        .filter((p) => p.company_id === this.company_id)
        .sort(function (a, b) {
          var textA = a.name.toUpperCase();
          var textB = b.name.toUpperCase();
          return textA < textB ? -1 : textA > textB ? 1 : 0;
        });
    },
  },
  methods: {
    clearFields() {
      const user = this.$store.state.AppActiveUser;
      this.itemLocal = Object.assign(this.itemLocal, {
        date: "",
        startHour: "",
        endHour: "",
        description: "",
        project_id: null,
      });
      this.activePrompt = false;
      this.handleClose();
    },
    addItem() {
      this.$validator.validateAll().then((result) => {
        if (result) {
          const payload = JSON.parse(JSON.stringify(this.itemLocal));
          payload.start_at = payload.date + " " + payload.startHour;
          payload.end_at = payload.date + " " + payload.endHour;
          this.$store
            .dispatch("hoursManagement/addItem", payload)
            .then((response) => {
              if (response.data.success) {
                this.$vs.loading.close();
                this.$vs.notify({
                  title: "Ajout d'un horaire",
                  text: `Horaire ajouté avec succès`,
                  iconPack: "feather",
                  icon: "icon-alert-circle",
                  color: "success",
                });
              } else {
                this.$vs.loading.close();
                this.$vs.notify({
                  title: "Une erreur est survenue",
                  text: response.data.error,
                  iconPack: "feather",
                  icon: "icon-alert-circle",
                  color: "danger",
                });
              }
            })
            .catch((error) => {
              this.$vs.loading.close();
              this.$vs.notify({
                title: "Une erreur est survenue",
                text: error.message,
                iconPack: "feather",
                icon: "icon-alert-circle",
                color: "danger",
              });
            });
          this.clearFields();
        }
      });
      this.handleClose();
    },
    definedMinEndHour() {
      if (this.itemLocal !== null && this.itemLocal.startHour !== null) {
        let result = parseInt(this.itemLocal.startHour.split(":")[0]) + 1;
        if (result !== NaN) {
          this.configEndHourPicker = result;
        }
      }
    },
  },
  created() {},
};
</script>
