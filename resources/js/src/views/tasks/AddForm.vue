<template>
  <div>
    <vs-button
      v-if="customTask == false"
      @click="activePrompt = true"
      class="w-full p-3 mb-4 mr-4"
    >Ajouter une tâche</vs-button>
    <div v-if="customTask == true" @click="activePrompt = true" class="card-task-add p-2 m-3">
      <feather-icon icon="PlusIcon" svgClasses="h-10 w-10" style="color: #fff" />
      <div style="font-size: 1.1em; color: #fff">Ajouter une tâche</div>
    </div>
    <vs-prompt
      title="Ajouter une tâche"
      accept-text="Ajouter"
      cancel-text="Annuler"
      button-cancel="border"
      @cancel="clearFields"
      @accept="addItem"
      @close="clearFields"
      :is-valid="validateForm"
      :active.sync="showPrompt"
      class="task-compose"
    >
      <div>
        <form class="add-task-form">
          <div class="vx-row">
            <!-- Left -->
            <div class="vx-col flex-1" style="border-right: 1px solid #d6d6d6;">
              <vs-input
                v-validate="'required'"
                name="name"
                class="w-full mb-4 mt-1"
                placeholder="Nom"
                v-model="itemLocal.name"
                :color="!errors.has('name') ? 'success' : 'danger'"
              />
              <span
                class="text-danger text-sm"
                v-show="errors.has('name')"
              >{{ errors.first('name') }}</span>

              <div class="my-3">
                <div>
                  <small class="date-label">Description</small>
                  <vs-textarea
                    rows="2"
                    label="Ajouter une description"
                    name="description"
                    class="w-full mb-1 mt-1"
                    v-model="itemLocal.description"
                  />
                </div>
              </div>
              <div class="my-3" v-if="previousTasks.length > 0">
                <span>Tache dépendante de :</span>
                <li :key="index" v-for="(item, index) in previousTasks">{{ item }}</li>
              </div>
              <div class="my-3" style="font-size: 0.9em;">
                <small class="date-label mb-1" style="display: block;">Date</small>
                <flat-pickr
                  :config="configdateTimePicker"
                  v-model="itemLocal.date"
                  placeholder="Date"
                  class="w-full"
                />
              </div>

              <div class="my-3">
                <vs-select
                  v-if="this.type !== 'users' && usersData.length > 0"
                  v-validate="'required'"
                  name="userId"
                  label="Attribuer"
                  v-model="itemLocal.user_id"
                  class="w-full"
                  autocomplete
                >
                  <vs-select-item
                    :key="index"
                    :value="item.id"
                    :text="item.firstname + ' ' + item.lastname"
                    v-for="(item,index) in usersData"
                  />
                </vs-select>
                <span
                  class="text-danger text-sm"
                  v-show="errors.has('userId')"
                >{{ errors.first('userId') }}</span>
              </div>

              <div class="my-3">
                <vs-select
                  v-if="this.type !== 'projects' && projectsData.length > 0"
                  v-validate="'required'"
                  name="projectId"
                  label="Projet"
                  v-model="itemLocal.project_id"
                  class="w-full"
                  autocomplete
                >
                  <vs-select-item
                    :key="index"
                    :value="item.id"
                    :text="item.name"
                    v-for="(item,index) in projectsData"
                  />
                </vs-select>
                <span
                  class="text-danger text-sm"
                  v-show="errors.has('userId')"
                >{{ errors.first('userId') }}</span>
              </div>

              <div class="my-3">
                <small class="date-label">Compétences</small>
                <vs-select
                  v-on:change="updateWorkareasList"
                  v-model="itemLocal.skills"
                  class="w-full"
                  multiple
                  autocomplete
                  name="skills"
                >
                  <vs-select-item
                    :key="index"
                    :value="item.id"
                    :text="item.name"
                    v-for="(item,index) in skillsData"
                  />
                </vs-select>
                <span
                  class="text-danger text-sm"
                  v-show="errors.has('skills')"
                >{{ errors.first('skills') }}</span>
              </div>

              <div
                class="my-3"
                v-if="this.type !== 'workarea' && ( itemLocal.skills.length > 0 && workareasDataFiltered.length > 0)"
              >
                <small class="date-label">Ilot</small>
                <vs-select name="workarea" v-model="itemLocal.workarea_id" class="w-full">
                  <vs-select-item
                    :key="index"
                    :value="item.id"
                    :text="item.name"
                    v-for="(item,index) in workareasDataFiltered"
                  />
                </vs-select>
              </div>
            </div>
            <!-- Right -->
            <div class="vx-col flex-5">
              <div class="mb-3" style="flex-direction: column; display: flex;">
                <add-previous-task
                  :addPreviousTask="addPreviousTask"
                  :tasks_list="tasks_list"
                  :previousTasksIds="itemLocal.previousTasksIds"
                />
                <span
                  v-if="!commentDisplay"
                  v-on:click="showComment"
                  class="linkTxt"
                >+ Ajouter un commentaire</span>
              </div>
              <div class="mb-4">
                <div v-if="orderDisplay">
                  <small class="date-label">Ordre</small>
                  <vs-input-number
                    min="1"
                    name="order"
                    class="inputNumber"
                    v-model="itemLocal.order"
                  />
                </div>
              </div>
              <ul class="mt-3">
                <li class="mr-3">
                  <vs-radio color="danger" v-model="itemLocal.status" vs-value="todo">A faire</vs-radio>
                </li>
                <li class="mr-3">
                  <vs-radio color="warning" v-model="itemLocal.status" vs-value="doing">En cours</vs-radio>
                </li>
                <li v-on:click="setTimeSpent">
                  <vs-radio color="success" v-model="itemLocal.status" vs-value="done">Terminé</vs-radio>
                </li>
              </ul>
              <div class="my-4 mt-3 mb-2">
                <small class="date-label">Temps estimé (en h)</small>
                <vs-input-number
                  min="1"
                  max="200"
                  name="estimatedTime"
                  class="inputNumber"
                  v-model="itemLocal.estimated_time"
                />
              </div>
              <div class="my-4 mt-0 mb-0" v-if="itemLocal.status == 'done'">
                <small class="date-label">Temps passé (en h)</small>
                <vs-input-number
                  min="1"
                  max="200"
                  name="timeSpent"
                  class="inputNumber"
                  v-model="itemLocal.time_spent"
                />
              </div>
            </div>
          </div>

          <div class="my-3">
            <div v-if="commentDisplay">
              <small class="date-label">Commentaires</small>
              <vs-textarea
                rows="2"
                label="Ajouter un commentaire"
                name="comment"
                class="w-full mb-1 mt-1"
                v-model="itemLocal.comment"
              />
            </div>
          </div>
        </form>
      </div>
    </vs-prompt>
  </div>
</template>

<script>
import flatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import { French as FrenchLocale } from "flatpickr/dist/l10n/fr.js";
import moment from "moment";
import { Validator } from "vee-validate";
import errorMessage from "./errorValidForm";

import AddPreviousTask from "./AddPreviousTasks.vue";

// register custom messages
Validator.localize("fr", errorMessage);

export default {
  components: {
    flatPickr,
    AddPreviousTask
  },
  props: {
    project_data: {
      required: true
    },
    tasks_list: { required: true },
    customTask: { type: Boolean },
    dateData: {
      type: Object
    },
    activeAddPrompt: {
      type: Boolean
    },
    handleClose: {
      type: Function
    },
    type: {
      type: String,
      required: true
    },
    idType: {
      type: Number,
      required: true
    }
  },
  data() {
    return {
      activePrompt: false,
      configdateTimePicker: {
        disableMobile: "true",
        enableTime: true,
        dateFormat: "d-m-Y H:i",
        locale: FrenchLocale
      },

      itemLocal: {
        name: "",
        order: "",
        description: "",
        date: new Date(),
        estimated_time: 1,
        time_spent: "",
        task_bundle_id: null,
        workarea_id: this.type === "workarea" ? this.idType : null,
        created_by: "",
        status: "todo",
        project_id: this.type === "projects" ? this.idType : null,
        comment: "",
        skills: [],
        previousTasksIds: [],
        user_id: this.type === "users" ? this.idType : null
      },

      workareasDataFiltered: [],
      comments: [],

      orderDisplay: false,
      descriptionDisplay: false,
      commentDisplay: false,
      have_setTimeSpent: false,
      previousTasks: []
    };
  },
  computed: {
    validateForm() {
      return (
        !this.errors.any() &&
        this.itemLocal.name != "" &&
        this.itemLocal.date != "" &&
        this.itemLocal.estimated_time != "" &&
        this.itemLocal.user_id != null &&
        this.itemLocal.project_id != null
      );
    },
    workareasData() {
      let $workareasData = this.$store.state.workareaManagement.workareas;
      let $filteredItems = this.filterItemsAdmin($workareasData);
      return $filteredItems;
    },
    skillsData() {
      if (this.type === "workarea") {
        console.log([
          "this.$store.state.skillManagement.skills",
          this.$store.state.skillManagement.skills
        ]);

        let workarea = this.$store.state.workareaManagement.workareas.find(
          w => w.id === this.idType || w.id === this.idType.toString()
        );
        console.log(["workarea", workarea]);
        if (workarea.skills !== []) {
          let $skillsData = [];
          workarea.skills.forEach(s => {
            $skillsData.push(s);
          });
          console.log(["$skillsData", $skillsData]);

          return this.filterItemsAdmin($skillsData);
        }
      } else {
        let $skillsData = this.$store.state.skillManagement.skills;
        return this.filterItemsAdmin($skillsData);
      }
    },
    usersData() {
      return this.$store.state.userManagement.users;
    },
    projectsData() {
      return this.$store.state.projectManagement.projects;
    },
    showPrompt: {
      get() {
        if (this.activeAddPrompt) {
          console.log("pass to change");
          console.log(["this.itemLocal.date", this.itemLocal.date]);
          console.log(["this.dateData.dateStr", this.dateData.date]);

          this.itemLocal.date = this.dateData.date;
        }
        return this.activeAddPrompt ? true : this.activePrompt ? true : false;
      },
      set(value) {
        return value;
      }
    }
  },
  methods: {
    clearFields() {
      Object.assign(this.itemLocal, {
        name: "",
        order: "",
        description: "",
        date: new Date(),
        estimated_time: 1,
        time_spent: "",
        task_bundle_id: null,
        workarea_id: "null",
        created_by: "",
        status: "todo",
        skills: [],
        project_id: null,
        comment: "",
        previousTasksIds: [],
        user_id: null
      });
      if (this.activeAddPrompt) {
        this.handleClose();
      } else {
        this.activePrompt = false;
      }
      this.orderDisplay = false;
      this.descriptionDisplay = false;
      this.commentDisplay = false;
      this.have_setTimeSpent = false;
      (this.previousTasks = []), Object.assign(this.workareasDataFiltered, []);
    },
    addItem() {
      this.$validator.validateAll().then(result => {
        this.itemLocal.date = moment(
          this.itemLocal.date,
          "DD-MM-YYYY HH:mm"
        ).format("YYYY-MM-DD HH:mm");
        this.itemLocal.workarea_id =
          this.itemLocal.workarea_id == "null"
            ? null
            : this.itemLocal.workarea_id;
        this.itemLocal.project_id =
          this.type === "projects" ? this.idType : this.itemLocal.project_id;
        this.itemLocal.user_id =
          this.type === "ursers" ? this.idType : this.itemLocal.user_id;
        this.itemLocal.workarea_id =
          this.type === "workarea" ? this.idType : this.itemLocal.workarea_id;

        console.log(["this.itemLocal", this.itemLocal]);

        if (result) {
          console.log(["result", result]);

          this.$store

            .dispatch(
              "taskManagement/addItem",
              Object.assign({}, this.itemLocal)
            )
            .then(() => {
              this.$vs.loading.close();
              this.$vs.notify({
                title: "Ajout d'une tâche",
                text: `"${this.itemLocal.name}" ajouté avec succès`,
                iconPack: "feather",
                icon: "icon-alert-circle",
                color: "success"
              });
              this.clearFields();
            })
            .catch(error => {
              this.$vs.loading.close();
              this.$vs.notify({
                title: "Error",
                text: error.message,
                iconPack: "feather",
                icon: "icon-alert-circle",
                color: "danger"
              });
            });
        }
      });
    },
    updateWorkareasList(ids) {
      this.workareasDataFiltered = this.workareasData.filter(function(
        workarea
      ) {
        for (let i = 0; i < ids.length; i++) {
          if (workarea.skills.filter(skill => skill.id == ids[i]).length == 0) {
            return false;
          }
        }
        return true;
      });
    },
    filterItemsAdmin($items) {
      let projectData = this.$store.state.projectManagement.projects.find(
        p => p.id === this.itemLocal.project_id
      );
      console.log(["projectData", projectData]);

      let $filteredItems = [];
      const user = this.$store.state.AppActiveUser;
      if (user.roles && user.roles.length > 0) {
        if (
          user.roles.find(
            r => r.name === "superAdmin" || r.name === "littleAdmin"
          )
        ) {
          if (this.project_data !== undefined && this.project_data !== null) {
            $filteredItems = $items.filter(
              item => item.company_id === this.project_data.company_id
            );
          } else if (projectData !== undefined && projectData !== null) {
            $filteredItems = $items.filter(
              item => item.company_id === projectData.company_id
            );
          }
        } else {
          $filteredItems = $items;
        }
      }
      return $filteredItems;
    },
    showComment() {
      this.commentDisplay = true;
    },
    addPreviousTask(taskIds) {
      this.itemLocal.previousTasksIds = taskIds;
      let previousTasks_local = [];

      taskIds.forEach(id => {
        let task = this.tasks_list.filter(t => t.id == id);
        previousTasks_local.push(task[0].name);
      });
      this.previousTasks = previousTasks_local;
    },
    setTimeSpent() {
      if (!this.have_setTimeSpent) {
        this.itemLocal.time_spent = this.itemLocal.estimated_time;
        this.have_setTimeSpent = true;
      }
    }
  },
  created() {
    console.log(["this.project_data", this.project_data]);
    console.log(["this.$store.state", this.$store.state]);
  }
};
</script>
<style>
.con-vs-dialog.task-compose .vs-dialog {
  max-width: 700px;
}
.add-task-form {
  max-height: 450px;
  overflow-y: auto;
  overflow-x: hidden;
}
.inputNumber {
  justify-content: start;
  max-width: 97px;
}
.linkTxt {
  font-size: 0.8em;
  color: #2196f3;
  background-color: #e9e9ff;
  border-radius: 4px;
  margin: 3px;
  padding: 3px 4px;
  font-weight: 500;
}
.linkTxt:hover {
  cursor: pointer;
  background-color: #efefef;
}
</style>
