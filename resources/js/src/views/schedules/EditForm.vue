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
      <form autocomplete="off">
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
            <span class="text-danger text-sm">{{ errors.first("title") }}</span>

            <p class="mt-4">Date de début</p>
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
              >{{ errors.first("startDate") }}</span
            >

            <p class="mt-4">Date de fin</p>
            <flat-pickr
              label="Date de fin"
              v-validate="'required'"
              name="endDate"
              :config="configDatePicker()"
              class="w-full"
              v-model="itemLocal.end"
              :color="!errors.has('endDate') ? 'success' : 'danger'"
            />
            <span class="text-danger text-sm" v-show="errors.has('endDate')">{{
              errors.first("endDate")
            }}</span>

            <p class="mt-4">Attribuer</p>
            <vs-select
              v-if="usersData.length > 0"
              v-validate="'required'"
              name="userId"
              v-model="itemLocal.user_id"
              class="w-full"
              autocomplete
            >
              <vs-select-item
                :key="index"
                :value="item.id"
                :text="item.firstname + ' ' + item.lastname"
                v-for="(item, index) in usersData"
              />
            </vs-select>
            <span class="text-danger text-sm" v-show="errors.has('userId')">{{
              errors.first("userId")
            }}</span>

            <p
              class="mt-4"
              v-if="workareasSkillsData && workareasSkillsData.length > 0"
            >
              Pôle de produciton
            </p>
            <vs-select
              v-if="workareasSkillsData && workareasSkillsData.length > 0"
              v-validate="'required'"
              name="projectId"
              v-model="itemLocal.workarea_id"
              class="w-full"
              autocomplete
            >
              <vs-select-item
                :key="index"
                :value="item.id"
                :text="item.name"
                v-for="(item, index) in workareasSkillsData"
              />
            </vs-select>
            <span
              class="text-danger text-sm"
              v-show="errors.has('projectId')"
              >{{ errors.first("projectId") }}</span
            >
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
        >Supprimer la tâche</vs-button
      >
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
    flatPickr,
  },
  props: {
    itemId: {
      type: Number,
      required: true,
    },
    type: {
      type: String,
      required: true,
    },
    idType: {
      type: Number,
      required: true,
    },
  },
  data() {
    return {
      itemLocal: Object.assign(
        {},
        this.$store.getters["scheduleManagement/getEvent"](this.itemId)
      ),
      workareasSkillsData: {},
      deleteWarning: false,
      configDatePicker: () => ({
        disableMobile: "true",
        enableTime: true,
        locale: FrenchLocale,
        dateFormat: "Y-m-d H:i",
        altFormat: "j F Y, H:i",
        altInput: true,
      }),
    };
  },
  computed: {
    activePrompt: {
      get() {
        if (this.itemId && this.itemId > -1) {
          this.itemLocal = Object.assign(
            {},
            this.$store.getters["scheduleManagement/getEvent"](this.itemId)
          );
        }
        this.getWorkareasData();
        return this.itemId && this.itemId > -1 && this.deleteWarning === false
          ? true
          : false;
      },
      set(value) {
        this.$store
          .dispatch("scheduleManagement/editEvent", {})
          .then(() => {})
          .catch((err) => {
            console.error(err);
          });
      },
    },
    validateForm() {
      return (
        !this.errors.any() &&
        this.itemLocal.title !== "" &&
        this.itemLocal.start !== "" &&
        this.itemLocal.end !== "" &&
        this.itemLocal.start < this.itemLocal.end
      );
    },
    usersData() {
      return this.$store.state.userManagement.users;
    },
  },
  methods: {
    init() {
      this.itemLocal = Object.assign(
        {},
        this.$store.getters["scheduleManagement/getEvent"](this.itemId)
      );
    },
    async getWorkareasData() {
      // get all workareas
      let allWorkareas = this.$store.state.workareaManagement.workareas;

      let taskSkills = [];
      // check if task selected to edit
      var workareas = [];
      if (this.itemId) {
        // get current task skills
        await this.$store
          .dispatch("skillManagement/fetchItems", { task_id: this.itemId })
          .then((data) => {
            taskSkills = data.payload;
            if (taskSkills !== []) {
              allWorkareas.forEach((aW) => {
                let missTask = false;
                // keep workareas have skills task
                taskSkills.forEach((ts) => {
                  if (
                    aW.skills.find((s) => s.id === ts.skill_id) !== undefined
                  ) {
                  } else {
                    missTask = true;
                  }
                });
                if (!missTask) {
                  workareas.push(aW);
                }
              });
            }
          })
          .catch((err) => {
            console.error(err);
          });
        this.workareasSkillsData = workareas;
      } else {
        this.workareasSkillsData = workareas;
      }
    },
    submitTodo() {
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
        user_id: this.itemLocal.user_id,
        project_is: this.itemLocal.project_is,
        status: this.itemLocal.status,
        from: "schedule",
      };
      this.$store
        .dispatch("taskManagement/updateItem", itemToSave)
        .then((data) => {
          //console.log(["data", data]);
        })
        .catch((err) => {
          //console.error(err);
        });

      this.$store.dispatch("scheduleManagement/updateEvent", this.itemLocal);
      this.$store.dispatch("scheduleManagement/editEvent", {});
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
        cancelText: "Annuler",
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
        .catch((err) => {
          console.error(err);
        });

      this.$store
        .dispatch("taskManagement/removeItem", this.itemLocal.id)
        .then(() => {})
        .catch((err) => {
          console.error(err);
        });

      this.init();
    },
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
  },
};
</script>
