<template>
  <vs-prompt
    title="Edition"
    accept-text="Modifier"
    cancel-text="Annuler"
    button-cancel="border"
    @cancel="init"
    @accept="submitTodo"
    @close="init"
    :is-valid="validateForm"
    :active.sync="activePrompt"
  >
    <div>
      <form>
        <div class="vx-row">
          <div class="vx-col w-full">
            <p>Nom</p>
            <vs-input
              v-validate="'required'"
              v-model="itemLocal.title"
              data-vv-validate-on="blur"
              name="title"
              class="w-full"
            />
            <span class="text-danger text-sm">{{ errors.first('title') }}</span>

            <p class="mt-5">Date de début</p>
            <flat-pickr
              v-validate="'required'"
              name="startDate"
              :config="configDatePicker()"
              class="w-full"
              v-model="itemLocal.start"
              :color="!errors.has('startDate') ? 'success' : 'danger'"
            />
            <span
              class="text-danger text-sm"
              v-show="errors.has('startDate')"
            >{{ errors.first('startDate') }}</span>

            <p class="mt-5">Date de fin</p>
            <flat-pickr
              v-validate="'required'"
              name="endDate"
              :config="configDatePicker()"
              class="w-full"
              v-model="itemLocal.end"
              :color="!errors.has('endDate') ? 'success' : 'danger'"
            />
            <span
              class="text-danger text-sm"
              v-show="errors.has('endDate')"
            >{{ errors.first('endDate') }}</span>
          </div>
        </div>
      </form>
    </div>
    <vs-row class="mt-5" vs-type="flex" vs-justify="flex-end">
      <vs-button
        @click="confirmDeleteTask(itemLocal.id)"
        color="danger"
        type="filled"
        size="small"
      >Supprimer la tâche</vs-button>
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

// Store Module
import moduleScheduleManagement from "@/store/schedule-management/moduleScheduleManagement.js";
import moduleTaskManagement from "@/store/task-management/moduleTaskManagement.js";

//import moduleRoleManagement from "@/store/role-management/moduleRoleManagement.js";

import moment from "moment";

// FlatPickr
import flatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import { French as FrenchLocale } from "flatpickr/dist/l10n/fr.js";

export default {
  components: {
    flatPickr
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
        this.$store.getters["scheduleManagement/getEvent"](this.itemId)
      ),
      deleteWarning: false,
      configDatePicker: () => ({
        disableMobile: "true",
        enableTime: true,
        locale: FrenchLocale,
        dateFormat: "Y-m-d H:i",
        altFormat: "j F Y, H:i",
        altInput: true
      })
    };
  },
  computed: {
    activePrompt: {
      get() {
        console.log(["itemId", this.itemId]);
        if (this.itemId && this.itemId > -1) {
          this.itemLocal = Object.assign(
            {},
            this.$store.getters["scheduleManagement/getEvent"](this.itemId)
          );
        }
        return this.itemId && this.itemId > -1 && this.deleteWarning === false
          ? true
          : false;
      },
      set(value) {
        this.$store
          .dispatch("scheduleManagement/editEvent", {})
          .then(() => {})
          .catch(err => {
            console.error(err);
          });
      }
    },
    validateForm() {
      return (
        !this.errors.any() &&
        this.itemLocal.title !== "" &&
        this.itemLocal.start !== "" &&
        this.itemLocal.end !== "" &&
        this.itemLocal.start < this.itemLocal.end
      );
    }
  },
  methods: {
    init() {
      this.itemLocal = Object.assign(
        {},
        this.$store.getters["scheduleManagement/getEvent"](this.itemId)
      );
    },
    submitTodo() {
      console.log(["id", this.itemLocal.id]);
      var itemToSave = {};
      //Parse new item to update task
      var itemToSave = {
        id: this.itemLocal.id,
        name: this.itemLocal.title,
        date: this.itemLocal.start,
        estimated_time: moment
          .duration(
            moment(this.itemLocal.end, "YYYY/MM/DD HH:mm").diff(
              moment(this.itemLocal.start, "YYYY/MM/DD HH:mm")
            )
          )
          .asHours(),
        order: this.itemLocal.order,
        description: this.itemLocal.description,
        time_spent: this.itemLocal.time_spent,
        workarea_id: this.itemLocal.workarea_id,
        status: this.itemLocal.status,
        from: "schedule"
      };
      console.log(["itemToSave", itemToSave]);
      this.$store
        .dispatch("taskManagement/updateItem", itemToSave)
        .then(data => {
          console.log(["data", data]);
        })
        .catch(err => {
          //console.error(err);
        });

      this.$store.dispatch("scheduleManagement/updateEvent", this.itemLocal);
      this.$store.dispatch("scheduleManagement/editEvent", {});
      console.log(["state", this.$store.state]);
    },
    confirmDeleteTask(idEvent) {
      this.deleteWarning = true;
      this.$vs.dialog({
        type: "confirm",
        color: "danger",
        title: "Confirmer suppression",
        text: `Vous allez supprimer la tâche "${this.itemLocal.title}"`,
        accept: this.deleteTask,
        cancel: this.keepTask,
        acceptText: "Supprimer !",
        cancelText: "Annuler"
      });
    },
    keepTask() {
      this.deleteWarning = false;
    },
    deleteTask() {
      this.deleteWarning = false;
      this.$store
        .dispatch("scheduleManagement/removeEvent", this.idEvent)
        .then(() => {})
        .catch(err => {
          console.error(err);
        });
      console.log(["idEvent", this.idEvent]);

      this.$store
        .dispatch("taskManagement/removeItem", this.itemLocal.id)
        .then(() => {})
        .catch(err => {
          console.error(err);
        });

      this.init();
      console.log(["this.store", this.$store.state]);
    }
  },
  created() {
    if (!moduleScheduleManagement.isRegistered) {
      this.$store.registerModule(
        "scheduleManagement",
        moduleScheduleManagement
      );
      moduleScheduleManagement.isRegistered = true;
    }
  }
};
</script>
