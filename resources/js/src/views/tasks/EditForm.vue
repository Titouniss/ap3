<template>
    <div class="p-3 mb-4 mr-4">
      <vs-prompt
          title="Mofidier une tâche"
          accept-text= "Modifier"
          cancel-text= "Annuler"
          button-cancel = "border"
          @cancel="clear"
          @accept="submitItem"
          @close="clear"
          :is-valid="validateForm"
          :active.sync="activePrompt"
          class="task-compose">
          <div>
            <form class="edit-task-form">
                <div class="vx-row">
                  <!-- Left -->
                  <div class="vx-col flex-1" style="border-right: 1px solid #d6d6d6;">
                    <vs-input v-validate="'required'" name="name" class="w-full mb-4 mt-1" placeholder="Nom" v-model="itemLocal.name" :color="!errors.has('name') ? 'success' : 'danger'" />
                    <span class="text-danger text-sm" v-show="errors.has('name')">{{ errors.first('name') }}</span>
                    
                    <div class="my-3">
                      <div v-if="descriptionDisplay || (itemLocal.description != null && itemLocal.description != '')">
                        <small class="date-label">Description</small>
                        <vs-textarea rows="2" label="Ajouter une description" name="description" class="w-full mb-1 mt-1" v-model="itemLocal.description" />  
                      </div>
                    </div>
                    <div class="my-3" v-if="previousTasks.length > 0">
                       <span> Tache dépendante de : </span> 
                        <li :key="index" v-for="(item, index) in previousTasks">
                          {{ item }}
                        </li>
                    </div>
                    <div class="my-3" style="font-size: 0.9em;">
                      <small class="date-label mb-1" style="display: block;">Date</small>
                      <flat-pickr :config="configdateTimePicker" v-model="itemLocal.date" placeholder="Date" class="w-full"/>
                    </div>

                    <div class="my-3">
                      <small class="date-label">Compétences</small>
                      <vs-select v-on:change="updateWorkareasList" v-model="itemLocal.skills" class="w-full" multiple autocomplete
                        v-validate="'required'" name='skills'>
                        <vs-select-item :key="index" :value="item.id" :text="item.name" v-for="(item,index) in skillsData" />
                      </vs-select>
                      <span class="text-danger text-sm" v-show="errors.has('skills')">{{ errors.first('skills') }}</span>
                    </div>
                    
                    <span
                      v-if="itemLocal.skills.length > 0 && workareasDataFiltered.length == 0"
                      class="text-danger text-sm"
                    >Attention, aucun îlot ne possède cette combinaison de compétences</span>

                    <!-- <div class="my-3" v-if="itemLocal.skills.length > 0 && workareasDataFiltered.length > 0">
                      <small class="date-label">Ilot</small>
                        <vs-select name="workarea" v-model="itemLocal.workarea_id" class="w-full">
                            <vs-select-item :key="index" :value="item.id" :text="item.name" v-for="(item,index) in workareasDataFiltered" />
                        </vs-select>
                    </div> -->
                  </div>
                  <!-- Right -->
                  <div class="vx-col flex-5">
                    <div class="mb-3" style="flex-direction: column; display: flex;">
                      <add-previous-task :addPreviousTask="addPreviousTask" :tasks_list="tasks_list" :previousTasksIds="itemLocal.previousTasksIds" :current_task_id="this.itemId"/> 
                      <span v-if="!descriptionDisplay && (itemLocal.description == null || itemLocal.description == '')" v-on:click="showDescription" class="linkTxt"> + Ajouter une description </span>
                    </div>
                    <div class="mb-4">
                      <div v-if="orderDisplay">
                        <small class="date-label">Ordre</small>
                        <vs-input-number min="1" name="order" class="inputNumber" v-model="itemLocal.order" />
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
                      <vs-input-number min="1" name="estimatedTime" class="inputNumber" v-model="itemLocal.estimated_time" />
                    </div>
                    <div class="my-4 mt-0 mb-0" v-if="itemLocal.status == 'done'">
                      <small class="date-label">Temps passé (en h)</small>
                      <vs-input-number min="1" name="timeSpent" class="inputNumber" v-model="itemLocal.time_spent" />
                    </div>
                  </div>
                </div>

                <div class="my-3">
                  <div>
                    <small class="date-label">Commentaires</small>
                  </div>
                  <vs-textarea
                    rows="1"
                    label="Ajouter un commentaire"
                    name="comment"
                    class="w-full mb-1 mt-1"
                    v-model="itemLocal.comment"
                    :color="validateForm ? 'success' : 'danger'"
                  />
                  <div class="mt-2">
                    <vs-button
                      v-if="itemLocal.comment != null && itemLocal.comment != ''"
                      color="success"
                      type="filled"
                      size="small"
                      style="margin-left: 5px"
                      v-on:click="addComment"
                      id="button-with-loading"
                      class="vs-con-loading__container"
                    >Ajouter</vs-button>
                  </div>
                  <span
                    class="no-comments"
                    v-if="itemLocal.comments && itemLocal.comments.length == 0"
                  >Aucun commentaire</span>
                  <div v-for="(comment, index) in itemLocal.comments" :key="index">
                    <div style="padding: 10px 0">
                      <vs-avatar
                        size="small"
                        :text="comment.creator.firstname + ' ' + comment.creator.lastname"
                      />
                      <span
                        class="comment-author"
                      >{{comment.creator.firstname + ' ' + comment.creator.lastname}},</span>
                      <span
                        class="comment-created-at"
                      >{{moment(comment.created_at)}} à {{momentTime(comment.created_at)}}</span>
                      <div class="comment-content">{{comment.description}}</div>
                    </div>
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
    itemId: {
      type: Number,
      required: true
    },
    companyId: {
      type: Number,
      required: true
    },
    tasks_list: { required: true }
  },
  data() {
    return {
      configdateTimePicker: {
        disableMobile: "true",
        enableTime: true,
        dateFormat: "d-m-Y H:i",
        locale: FrenchLocale
      },

      itemLocal: Object.assign(
        {},
        this.$store.getters["taskManagement/getItem"](this.itemId)
      ),

      workareasDataFiltered: [],
      comments: [],

      orderDisplay: false,
      descriptionDisplay: false,
      commentDisplay: false,
      have_setTimeSpent: false
    };
  },
  computed: {
    validateForm() {
      return (
        !this.errors.any() &&
        this.itemLocal.name != "" &&
        this.itemLocal.date != "" &&
        this.itemLocal.estimated_time != ""
      );
    },
    activePrompt: {
      get() {
        this.addPreviousTask(this.itemLocal.previous_tasks);
        return this.itemId && this.itemId > 0 ? true : false;
      },
      set(value) {
        this.$store
          .dispatch("taskManagement/editItem", {})
          .then(() => {})
          .catch(err => {
            console.error(err);
          });
      }
    },
    workareasData() {
      let $workareasData = this.$store.state.workareaManagement.workareas;
      let $filteredItems = this.filterItemsAdmin($workareasData);
      return $filteredItems;
    },
    skillsData() {
      let $skillsData = this.$store.state.skillManagement.skills;
      this.updateWorkareasList(this.itemLocal.skills);
      return this.filterItemsAdmin($skillsData);
    }
  },
  methods: {
    clear() {
      this.itemLocal = {};
      (this.workareasDataFiltered = []), (this.comments = []);
    },
    moment: function(date) {
      moment.locale("fr");
      return moment(date, "YYYY-MM-DD HH:mm:ss").format("DD MMMM YYYY");
    },
    momentTime: function(date) {
      moment.locale("fr");
      return moment(date, "YYYY-MM-DD HH:mm:ss").format("HH:mm");
    },
    submitItem() {
      if (this.itemLocal.comment != null && this.itemLocal.comment != "") {
        this.addComment();
      }

      this.$validator.validateAll().then(result => {
        this.itemLocal.date = moment(
          this.itemLocal.date,
          "DD-MM-YYYY HH:mm"
        ).format("YYYY-MM-DD HH:mm");
        // this.itemLocal.workarea_id =
        //   this.itemLocal.workarea_id == "null"
        //     ? null
        //     : this.itemLocal.workarea_id;

        if (result) {
          this.$store
            .dispatch(
              "taskManagement/updateItem",
              Object.assign({}, this.itemLocal)
            )
            .then(() => {
              this.$vs.loading.close();
              this.$vs.notify({
                title: "Modification d'une tâche",
                text: `"${this.itemLocal.name}" modifiée avec succès`,
                iconPack: "feather",
                icon: "icon-alert-circle",
                color: "success"
              });
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
    addComment() {
      this.$vs.loading({
        background: "success",
        color: "#fff",
        container: "#button-with-loading",
        scale: 0.3
      });
      this.$store
        .dispatch(
          "taskManagement/addComment",
          Object.assign({}, this.itemLocal)
        )
        .then(response => {
          this.itemLocal.comments = response;
          this.$vs.loading.close("#button-with-loading > .con-vs-loading");
          this.itemLocal.comment = "";
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
      let $filteredItems = [];
      const user = this.$store.state.AppActiveUser;
      if (user.roles && user.roles.length > 0) {
        if (
          user.roles.find(
            r => r.name === "superAdmin" || r.name === "littleAdmin"
          )
        ) {
          $filteredItems = $items.filter(
            item => item.company_id === this.companyId
          );
        } else {
          $filteredItems = $items;
        }
      }
      return $filteredItems;
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
    },
    showDescription() {
      this.descriptionDisplay = true;
    }
  }
};
</script>
<style>
.con-vs-dialog.task-compose .vs-dialog {
  max-width: 700px;
}
.edit-task-form {
  max-height: 600px;
  overflow-y: auto;
  overflow-x: hidden;
}
.no-comments {
  font-size: 0.9em;
  font-style: italic;
  margin-left: 1em;
}
.comment-author {
  font-size: 1em;
  font-weight: bold;
  vertical-align: top;
}
.comment-created-at {
  font-size: 0.8em;
  line-height: 2;
  vertical-align: top;
}
.comment-content {
  border: 1px solid #c3c3c3;
  border-radius: 5px;
  padding: 3px;
  font-size: 0.9em;
  margin: -17px 35px 0 35px;
  display: table;
}
</style>
