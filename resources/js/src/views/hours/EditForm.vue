<template>
  <vs-prompt
    title="Edition"
    accept-text="Modifier"
    cancel-text="Annuler"
    button-cancel="border"
    @cancel="init"
    @accept="submitHour"
    @close="init"
    :is-valid="validateForm"
    :active.sync="activePrompt"
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
    <vs-row class="mt-5" vs-type="flex" vs-justify="flex-end">
      <vs-button
        @click="confirmDeleteHour(itemLocal.id)"
        color="danger"
        type="filled"
        size="small"
      >Supprimer l'horaire</vs-button>
    </vs-row>
  </vs-prompt>
</template>

<script>
// register custom messages
import { Validator } from "vee-validate";
import errorMessage from "./errorValidForm";
Validator.localize("fr", errorMessage);
var model = "tâche";
var modelPlurial = "tâches";

import moment from "moment";
import vSelect from "vue-select";

// FlatPickr
import flatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import { French as FrenchLocale } from "flatpickr/dist/l10n/fr.js";

export default {
  components: {
    flatPickr,
    vSelect
  },
  props: {
    itemId: {
      type: Number,
      required: true
    }
  },
  data() {
    return {
      itemLocal: Object.assign(
        {},
        this.$store.getters["hoursManagement/getItem"](this.itemId)
      ),
      workareasSkillsData: {},
      deleteWarning: false,
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
    activePrompt: {
      get() {
        if (this.itemId && this.itemId > -1) {
          this.itemLocal = Object.assign(
            {},
            this.$store.getters["hoursManagement/getItem"](this.itemId)
          );
        }

        return this.itemId && this.itemId > -1 && this.deleteWarning === false
          ? true
          : false;
      },
      set(value) {
        this.$store
          .dispatch("hoursManagement/editItem", {})
          .then(() => {})
          .catch(err => {
            console.error(err);
          });
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
    }
  },
  methods: {
    init() {
      this.itemLocal = Object.assign(
        {},
        this.$store.getters["hoursManagement/getItem"](this.itemId)
      );
    },
    submitHour() {
      var itemToSave = {};

      //Parse new item to update task
      var itemToSave = {
        id: this.itemLocal.id,
        date: this.itemLocal.date,
        description: this.itemLocal.description,
        user_id: this.itemLocal.user_id,
        project_id: this.itemLocal.project_id,
        start_at: this.itemLocal.date + " " + this.itemLocal.startHour,
        end_at: this.itemLocal.date + " " + this.itemLocal.endHour
      };

      this.$store
        .dispatch("hoursManagement/updateItem", itemToSave)
        .then(response => {
          if (response.data.success) {
            this.$vs.loading.close();
            this.$vs.notify({
              title: "Modification d'un horaire",
              text: `Horaire modifié avec succès`,
              iconPack: "feather",
              icon: "icon-alert-circle",
              color: "success"
            });
          } else {
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
        .catch(err => {
          this.$vs.loading.close();
          this.$vs.notify({
            title: "Une erreur est survenue",
            text: err.message,
            iconPack: "feather",
            icon: "icon-alert-circle",
            color: "danger"
          });
        });

      this.$store.dispatch("hoursManagement/editItem", {});
    },
    confirmDeleteHour(idHour) {
      this.deleteWarning = true;
      this.$vs.dialog({
        type: "confirm",
        color: "danger",
        title: "Confirmer suppression",
        text: `Vous allez supprimer la saisie horaire "${this.itemLocal.date +
          " " +
          this.itemLocal.startHour +
          " -> " +
          this.itemLocal.endHour}"`,
        accept: this.deleteHour,
        cancel: this.keepHour,
        acceptText: "Supprimer",
        cancelText: "Annuler"
      });
    },
    keepHour() {
      this.deleteWarning = false;
    },
    deleteHour() {
      this.deleteWarning = false;
      this.$store
        .dispatch("hoursManagement/removeItem", this.itemLocal.id)
        .then(() => {
          this.$vs.loading.close();
          this.$vs.notify({
            title: "Suppression d'un horaire",
            text: `Horaire supprimé avec succès`,
            iconPack: "feather",
            icon: "icon-alert-circle",
            color: "success"
          });
        })
        .catch(err => {
          this.$vs.loading.close();
          this.$vs.notify({
            title: "Une erreur est survenue",
            text: err.message,
            iconPack: "feather",
            icon: "icon-alert-circle",
            color: "danger"
          });
        });

      this.init();
      this.$store.dispatch("hoursManagement/editItem", {});
    }
  },
  mounted() {}
};
</script>
