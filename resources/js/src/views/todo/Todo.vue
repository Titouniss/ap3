<!-- =========================================================================================
  File Name: Todo.vue
  Description: Todo Application to keep you ahead of time
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
    Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
========================================================================================== -->


<template>
  <div
    id="todo-app"
    class="
      border border-solid
      d-theme-border-grey-light
      rounded
      relative
      overflow-hidden
    "
  >
    <vs-sidebar
      class="items-no-padding"
      parent="#todo-app"
      :click-not-close="clickNotClose"
      :hidden-background="clickNotClose"
      v-model="isSidebarActive"
    >
      <todo-add-new :todo_list="todoData" />

      <component
        :is="scrollbarTag"
        class="todo-scroll-area"
        :settings="settings"
        :key="$vs.rtl"
      >
        <div class="todo__filters-container">
          <!-- all -->
          <div class="px-6 py-5">
            <a
            
              style="color: gray; font-weight: 500 "
              class="flex cursor-pointer"
              type="transparent"
              @click="filtersImportantAndCompleted.is_completed = 0"
              v-on:click="filtersImportantAndCompleted.is_important = 0"
              v-on:click.stop="filtersImportantAndCompleted.tags = null"
            >
              <feather-icon
                icon="LayersIcon"
                :style="{'color': filtersImportantAndCompleted.is_completed == 0 
              && filtersImportantAndCompleted.is_important == 0  
              && filtersImportantAndCompleted.tags == null  ? 'rgba(var(--vs-success), 1)' : 'gray' }"
                :svgClasses="['h-6 w-6']"
              ></feather-icon>
              <span 
              :style="{'color': filtersImportantAndCompleted.is_completed == 0 
              && filtersImportantAndCompleted.is_important == 0  
              && filtersImportantAndCompleted.tags == null  ? 'rgba(var(--vs-success), 1)' : 'gray' }"
              class="text-lg ml-3">Tous</span>
            </a>
          </div>

          <vs-divider></vs-divider>

          <!-- starred -->
          <div class="px-6 py-4">
            <h5>Filtres</h5>
            <template>
              <a
                style="color: gray; font-weight: 500"
                class="flex mt-6 cursor-pointer"
                type="transparent"
                @click="filtersImportantAndCompleted.is_completed = 1"
                v-on:click="filtersImportantAndCompleted.is_important = 0"
                v-on:click.stop="filtersImportantAndCompleted.tags = null"
              >
                <feather-icon
                  icon="CheckIcon"
                  :svgClasses="['h-6 w-6']"
                 :style="{'color': filtersImportantAndCompleted.is_completed == 1
                  && filtersImportantAndCompleted.is_important == 0  
                  && filtersImportantAndCompleted.tags == null  ? 'rgba(var(--vs-success), 1)' : 'gray' }" 
                ></feather-icon>
                <span
                 :style="{'color': filtersImportantAndCompleted.is_completed == 1
                  && filtersImportantAndCompleted.is_important == 0  
                  && filtersImportantAndCompleted.tags == null  ? 'rgba(var(--vs-success), 1)' : 'gray' }" 
                class="text-lg ml-3">Complété</span>
              </a>

              <a
                style="color: gray; font-weight: 500"
                class="flex mt-6 cursor-pointer"
                type="transparent"
                @click="filtersImportantAndCompleted.is_important = 1"
                v-on:click="filtersImportantAndCompleted.is_completed = 0"
                v-on:click.stop="filtersImportantAndCompleted.tags = null"
              >
                <feather-icon
                  icon="StarIcon"
                  :svgClasses="['h-6 w-6']"
                   :style="{'color': filtersImportantAndCompleted.is_completed == 0
                  && filtersImportantAndCompleted.is_important == 1
                  && filtersImportantAndCompleted.tags == null  ? 'rgba(var(--vs-success), 1)' : 'gray' }"  
                ></feather-icon>
                <span
                :style="{'color': filtersImportantAndCompleted.is_completed == 0
                  && filtersImportantAndCompleted.is_important == 1
                  && filtersImportantAndCompleted.tags == null  ? 'rgba(var(--vs-success), 1)' : 'gray' }"  
                class="text-lg ml-3">Important</span>
              </a>
            </template>
          </div>

          <vs-divider></vs-divider>
          <div class="w-full px-6 py-2">
            <todo-add-tags tag_list="tagData" />

            <h5>Tags</h5>
            <div class="todo__lables-list" v-if="tagData.length > 0">
              <a
                class="flex items-center mt-6 cursor-pointer w-full"
                style="color: #2c2c2c"
                name="tag"
                v-for="(tag, index) in tagData"
                type="transparent"
                :key="index"
                @click="filtersImportantAndCompleted.tags = tag.id"
              >
                <span
                  class="ml-1 h-3 w-3 rounded-full mr-4 border-solid mt-1"
                  :style="`background-color: ${tag.color}; border-color:${tag.color}`"
                />
                <span class="text-lg">{{ tag.title }}</span>
                <div class="vx-col w-full sm:w-1/6 ml-auto flex sm:justify-end">

                <feather-icon
                    icon="Edit3Icon"
                    class="cursor-pointer hover:text-primary "
                    svgClasses="w-5 h-5"
                    @click.Stop="showDisplayPromptTag(tag)"

                  />
  
                  <feather-icon
                    icon="Trash2Icon"
                    class="cursor-pointer hover:text-danger "
                    svgClasses="w-5 h-5"
                    @click.stop="moveToTrash(tag)"
                  />
                </div>
              </a>
            </div>
          </div>
        </div>
      </component>
    </vs-sidebar>
    <div
      :class="{ 'sidebar-spacer': clickNotClose }"
      class="
        no-scroll-content
        border border-r-0 border-b-0 border-t-0 border-solid
        d-theme-border-grey-light
        no-scroll-content
      "
    >
      <div
        class="
          flex
          d-theme-dark-bg
          items-center
          border border-l-0 border-r-0 border-t-0 border-solid
          d-theme-border-grey-light
        "
      >
        <!-- SEARCH BAR -->
        <vs-input
          icon-no-border
          size="large"
          icon-pack="feather"
          icon="icon-file-plus"
          placeholder="Ajouter une tâche pour aujourd'hui, Appuyer sur [Entrer] pour ajouter"
          class="vs-input-no-border vs-input-no-shdow-focus w-full"
          v-model="addLineQuery"
          v-on:keyup.enter="onAddTaskLine"
        />
        <vs-input
          icon-no-border
          size="large"
          icon-pack="feather"
          icon="icon-search"
          placeholder="Rechercher..."
          v-model="searchQuery"
          @input="updateSearchQuery"
          class="vs-input-no-shdow-focus"
        />
      </div>

      <!-- <div v-for="day in todoDate()" :key="day.id"> -->

      <!-- TODO LIST -->
      <component
        :is="scrollbarTag"
        class="todo-content-scroll-area todo-list"
        :settings="settings"
        ref="todoDataPS"
        :key="$vs.rtl"
      >
          <div v-if="todoData.length > 0 && this.filtersImportantAndCompleted.is_completed == true">
              <div v-if="tasksAfterTomorrow.length > 0">
            <h3 class="m-5">Plus tard dans la semaine</h3>
            <draggable
              tag="ul"
              :list="tasksAfterTomorrow"
              group="todo"
              :move="onMoveCallback"
              class="afterTomorrow"
            >
              <li
                v-for="(task, index) in tasksAfterTomorrow"
                :key="String(task.id)"
                :style="[{ transitionDelay: index * 0.1 + 's' }]"
                style="cursor: pointer"
              >
                <todo-task
                  :task_data="task"
                  @showDisplayPrompt="showDisplayPrompt(task)"
                  :key="String(task.title)"
                />
              </li>
            </draggable>
          </div>
          <div v-else />
   <div v-if="tasksTomorrow.length > 0">
            <h3 class="m-5">Demain</h3>
            <draggable
              tag="ul"
              :list="tasksTomorrow"
              group="todo"
              :move="onMoveCallback"
              class="tomorrow"
            >
              <li
                v-for="(task, index) in tasksTomorrow"
                :key="task.id"
                :style="[{ transitionDelay: index * 0.1 + 's' }]"
                style="cursor: pointer"
              >
                <todo-task
                  :task_data="task"
                  @showDisplayPrompt="showDisplayPrompt(task)"
                  :key="String(task.title)"
                />
              </li>
            </draggable>
          </div>
          <div v-else />

          <div v-if="tasksToday.length > 0">
            <h3 class="m-5">Aujourd'hui</h3>
            
            <draggable
              tag="ul"
              :list="tasksToday"
              group="todo"
              :move="onMoveCallback"
              class="today"
            >
              <li
                v-for="(task, index) in tasksToday"
                :key="String(task.id)"
                :style="[{ transitionDelay: index * 0.1 + 's' }]"
                style="cursor: pointer"
              >
                <todo-task
                  :task_data="task"
                  @showDisplayPrompt="showDisplayPrompt(task)"
                  :key="String(task.title)"
                />
              </li>
            </draggable>
          </div>
          <div v-else />
            
 <div v-if="tasksYesterday.length > 0">
            <h3 class="m-5">En retard</h3>
            <draggable
              tag="ul"
              :list="tasksYesterday"
              group="todo"
              :move="onMoveCallback"
              class="yesterday"
            >
              <li
                v-for="(task, index) in tasksYesterday"
                :key="String(task.id)"
                :style="[{ transitionDelay: index * 0.1 + 's' }]"
                style="cursor: pointer"
              >
                <todo-task
                  :task_data="task"
                  @showDisplayPrompt="showDisplayPrompt(task)"
                  :key="String(task.title)"
                />
              </li>
            </draggable>
          </div>
          <div v-else />
        </div>
    
        


        <!-- Toute les tâches  -->
        <div v-if="this.filtersImportantAndCompleted.is_completed == false">
          <div v-if="tasksYesterday.length > 0">
            <h3 class="m-5">En retard</h3>
            <draggable
              tag="ul"
              :list="tasksYesterday"
              group="todo"
              :move="onMoveCallback"
              class="yesterday"
            >
              <li
                v-for="(task, index) in tasksYesterday"
                :key="String(task.id)"
                :style="[{ transitionDelay: index * 0.1 + 's' }]"
                style="cursor: pointer"
              >
                <todo-task
                  :task_data="task"
                  @showDisplayPrompt="showDisplayPrompt(task)"
                  :key="String(task.title)"
                />
              </li>
            </draggable>
          </div>
          <div v-else />

            <h3 class="m-5">Aujourd'hui</h3>
                         <!-- <div v-if="!taskDisplay">
                                    <span
                                        v-on:click="showTask"
                                        class="linkTxt ml-5"
                                        style="font-size:12px"
                                      >
                                          + Ajouter une tâche
                                      </span>
                                  </div>   -->
                                    <!-- <div class="my-3">

                          <div v-if="taskDisplay" class="task_editor__editing_area"
        >
                        <form  autocomplete="off" v-on:submit.prevent>  
                            <span style="color: black"> Ajouter une tâche</span>
                          <div class="vx-col ml-auto flex justify-end mb-2">
                        
                          <feather-icon
                              icon="StarIcon"
                              class="cursor-pointer"
                              :svgClasses="[
                                { 'text-warning stroke-current': itemLocal.is_important },
                                'w-5',
                                'h-5 mr-4',
                            ]"
                            @click.prevent="itemLocal.is_important = !itemLocal.is_important"
                          ></feather-icon>
                         </div>
                            <vs-input
                                v-validate="'max:200|required'"
                                placeholder="ex: Réunion à 15h"
                                name="title"
                                class="w-full mr-20 mb-2"
                                v-model="itemLocal.title"
                                :success="itemLocal.title != '' && !errors.has('title')"
                                :danger="errors.has('title')"
                                :danger-text="errors.first('title')"   
                                
                            />
                             <simple-select
                                v-model="itemLocal.tags"
                                multiple
                                label="title"
                                header=""
                                name="tags"
                                :close-on-select="false"  
                                :options="tagData"
                                :reduce="item => item.id"
                                input-id="tags"
                                class="mb-2"
                              />
                             <flat-pickr
                                  v-validate="{
                                    required: true,
                                  }"
                                  v-model="itemLocal.due_date"
                                  class="w-full mb-2"
                                  header="Date d'embauche"
                                  name="due_date"
                                  :config="configdateTimePicker"
                                /> 

                             <vs-input
                                placeholder="Description"
                                name="description"
                                class="w-full mr-20 "
                                v-model="itemLocal.description"
                                
                            />
  
                         </form>
                        </div>
                      
                        <div class="mt-2 flex w-full"  v-if="taskDisplay"
>
                            <vs-button
                            v-if="itemLocal.title != '' &&itemLocal.due_date !='' "
                                color="success"
                                type="filled"
                                size="small"
                                style="margin-left: 5px"
                                v-on:click="addTodo"
                                @click="taskDisplay = false"
                                id="button-with-loading"
                                class="vs-con-loading__container"
                            >
                                Ajouter
                            </vs-button>
                     
                       
                            <vs-button
                                color="danger"
                                type="filled"
                                size="small"
                                style="margin-left: 5px"
                                v-on:click="taskDisplay = false"
                                @click="clearFields"
                                id="button-with-loading"
                                class="vs-con-loading__container"
                            >
                                Annuler
                            </vs-button>
                             
                        </div>
                       
                    </div> -->
            <draggable
              tag="ul"
              :list="tasksToday"
              group="todo"
              :move="onMoveCallback"
              class="today"
            >
              <li
                v-for="(task, index) in tasksToday"
                :key="String(task.id)"
                :style="[{ transitionDelay: index * 0.1 + 's' }]"
                style="cursor: pointer"
              >
                <todo-task
                  :task_data="task"
                  @showDisplayPrompt="showDisplayPrompt(task)"
                  :key="String(task.title)"
                />
              </li>
            </draggable>

          <div v-if="tasksTomorrow.length > 0">
            <h3 class="m-5">Demain</h3>
            <draggable
              tag="ul"
              :list="tasksTomorrow"
              group="todo"
              :move="onMoveCallback"
              class="tomorrow"
            >
              <li
                v-for="(task, index) in tasksTomorrow"
                :key="task.id"
                :style="[{ transitionDelay: index * 0.1 + 's' }]"
                style="cursor: pointer"
              >
                <todo-task
                  :task_data="task"
                  @showDisplayPrompt="showDisplayPrompt(task)"
                  :key="String(task.title)"
                />
              </li>
            </draggable>
          </div>
          <div v-else />

          <div v-if="tasksAfterTomorrow.length > 0">
            <h3 class="m-5">Plus tard dans la semaine</h3>
            <draggable
              tag="ul"
              :list="tasksAfterTomorrow"
              group="todo"
              :move="onMoveCallback"
              class="afterTomorrow"
            >
              <li
                v-for="(task, index) in tasksAfterTomorrow"
                :key="String(task.id)"
                :style="[{ transitionDelay: index * 0.1 + 's' }]"
                style="cursor: pointer"
              >
                <todo-task
                  :task_data="task"
                  @showDisplayPrompt="showDisplayPrompt(task)"
                  :key="String(task.title)"
                />
              </li>
            </draggable>
          </div>
          <div v-else />
        </div>
        <div v-if="todoData.length > 0"></div>
        <div v-else>
          <div class="text-center ">Vous n'avez actuellement aucune tâche</div>
        </div>
      </component>
    </div>
    <!-- /TODO LIST -->

    <!-- EDIT TODO DIALOG -->
    <todo-edit
      :displayPrompt="displayPrompt"
      :taskId="taskIdToEdit"
      @hideDisplayPrompt="hidePrompt"
      v-if="displayPrompt"
    ></todo-edit>
        <tag-edit
           :displayPrompt="displayPromptTag"
           :itemId="tagIdToEdit"
           @hideDisplayPrompt="hidePrompt"
           v-if="displayPromptTag"
        />
         
  </div>
</template>

<script>
import moduleTodo from "@/store/todo-management/moduleTodoManagement.js";
import moduleTag from "@/store/tag-management/moduleTagManagement.js";
import TodoAddNew from "./TodoAddNew.vue";
import TodoTask from "./TodoTask.vue";
import TodoEdit from "./TodoEdit.vue";
import TagEdit from "./TagEdit.vue";
import TodoAddTags from "./TodoAddTags.vue";
import VuePerfectScrollbar from "vue-perfect-scrollbar";
import SimpleSelect from "@/components/inputs/selects/SimpleSelect.vue";
import moment from "moment";
import draggable from "vuedraggable";
import { French as FrenchLocale } from "flatpickr/dist/l10n/fr.js";
import flatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";

export default {
  data() {
    return {
      props: {},
      itemLocal: {
        title: "",
        description: "",
        due_date: new Date(),
        is_completed: false,
        is_important: false,
        tags:[],
      },
      itemToDel: null,
        configdateTimePicker: {
        disableMobile: "true",
        dateFormat: "Y-m-d",
        altFormat: "d/m/Y",
        altInput: true,
        locale: FrenchLocale,
        minDate: new Date(
                    new Date().getFullYear(),
                    new Date().getMonth(), 
                    new Date().getDate(),
                  
                ),
                },
      dateValue: false,
      searchQuery: "",
      addLineQuery:"",
      clickNotClose: true,
      displayPrompt: false,
      displayPromptTag: false,
      taskIdToEdit: 0,
      tagIdToEdit: 0,
      isSidebarActive: true,
      taskDisplay: false,
      isSubmiting: false,
      filtersImportantAndCompleted: {
        is_important: false,
        is_completed: false,
        tags: null,
      },
      date: {},
      settings: {
        maxScrollbarLength: 60,
        wheelSpeed: 0.3,
      },
    };
  },
  watch: {
    filtersImportantAndCompleted: {
      handler(val, prev) {
        this.fetchTodo();
      },
      deep: true,
    },
    windowWidth() {
      this.setSidebarWidth();
    },
  },
  computed: {
    //date pour toutes les tâches
    todoData() {
      if (this.filtersImportantAndCompleted.is_completed == 1) {
        return this.$store.getters["todoManagement/getItems"].filter(
          (todo) => todo.is_completed == true
        ).slice(0,25);
      } else {
        return this.$store.getters["todoManagement/getItems"].filter(
          (todo) => todo.is_completed == false
        );
      }
    },
    tasksTomorrow() {
      return this.todoData.filter((x) =>
        moment(x.due_date).isSame(moment().add(1, "days").format("YYYY-MM-DD"))
      );
    },
    tasksAfterTomorrow() {
      return this.todoData.filter((x) =>
        moment(x.due_date).isAfter(moment().add(1, "days").format("YYYY-MM-DD"))
      );
    },
    tasksToday() {
      return this.todoData.filter((x) =>
        moment(x.due_date).isSame(moment(), "days")
      );
    },
    tasksYesterday() {
      return this.todoData.filter((x) =>
        moment(x.due_date).isBefore(moment().format("YYYY-MM-DD"))
      );
    },
    tagData() {
      return this.$store.getters["tagManagement/getItems"];
    },

    scrollbarTag() {
      return this.$store.getters.scrollbarTag;
    },
    windowWidth() {
      return this.$store.state.windowWidth;
    },
  },
  methods: {
       clearFields() {
      this.itemLocal={
          title: "",
          description: "",
          due_date: new Date(),
          is_completed: false,
          is_important: false,
          tags:[]
        };
        this.addLineQuery=""
    },
    addTodo() {
       if (this.isSubmiting) return;

            this.isSubmiting = true;
        const item = JSON.parse(JSON.stringify(this.itemLocal));
      this.$validator.validateAll().then((result) => {
        if (result) {
          this.$store
            .dispatch("todoManagement/addItem", item)
            .then((data) => {
              item.is_completed = false;
              item.is_important = false;
              this.clearFields(false);
              this.$vs.notify({
                title: "Ajout d'une tâche",
                text: `Tâche ajouté avec succès`,
                iconPack: "feather",
                icon: "icon-alert-circle",
                color: "success",
              });
            })
            .catch((error) => {
              this.$vs.notify({
                title: "Error",
                text: error.message,
                iconPack: "feather",
                icon: "icon-alert-circle",
                color: "danger",
              });
            }).finally(() => {
                    this.isSubmiting = false;
                    this.$vs.loading.close();
                });
          this.clearFields();
        }
      });
    },
    onAddTaskLine(){
      if (this.addLineQuery) {
          this.itemLocal={
            title: this.addLineQuery,
            description: "",
            due_date: new Date(),
            is_completed: false,
            is_important: false,
            tags:[]
          };
          const item = JSON.parse(JSON.stringify(this.itemLocal));
          this.$store
            .dispatch("todoManagement/addItem", item)
            .then((data) => {
              item.is_completed = false;
              item.is_important = false;
              this.clearFields(false);
              this.$vs.notify({
                title: "Ajout d'une tâche",
                text: `Tâche ajouté avec succès`,
                iconPack: "feather",
                icon: "icon-alert-circle",
                color: "success",
              });
            })
            .catch((error) => {
              this.$vs.notify({
                title: "Error",
                text: error.message,
                iconPack: "feather",
                icon: "icon-alert-circle",
                color: "danger",
              });
            }).finally(() => {
                    this.isSubmiting = false;
                    this.$vs.loading.close();
                });
          this.clearFields();
      }
    },
    showTask() {
        this.taskDisplay = true;
    },
     itemIdToEdit() {
       console.log(this.$store.getters["tagManagement/getSelectedItem"].id)
            return (
                this.$store.getters["tagManagement/getSelectedItem"].id ||
                0
            );
        },
    onMoveCallback(evt, originalEvent) {
      switch (evt.to.className) {
        case "yesterday":
          evt.draggedContext.element.due_date = moment()
            .subtract(1, "days")
            .format("YYYY-MM-DD");
          break;
        case "today":
          evt.draggedContext.element.due_date = moment().format("YYYY-MM-DD");
          break;
        case "tomorrow":
          evt.draggedContext.element.due_date = moment()
            .add(1, "days")
            .format("YYYY-MM-DD");
          break;
        case "afterTomorrow":
          evt.draggedContext.element.due_date = moment()
            .add(2, "days")
            .format("YYYY-MM-DD");
          break;
        default:
          break;
      }
      if (evt.draggedContext.element != null) {
        let todo = {
          id: evt.draggedContext.element.id,
          title: evt.draggedContext.element.title,
          is_completed: evt.draggedContext.element.is_completed,
          is_important: evt.draggedContext.element.is_important,
          due_date: evt.draggedContext.element.due_date,
          description: evt.draggedContext.element.description,
        };
        console.log(evt.dragged.parentNode)
        evt.dragged.parentNode.removeChild(evt.dragged);
        return this.$store.dispatch("todoManagement/updateItem", todo);
      }
    },
    moment: function () {
      return moment([]);
    },
    fetchTodo() {
      const that = this;
      this.$vs.loading();
      this.$store
        .dispatch("todoManagement/fetchItems", {
          q: this.searchQuery || undefined,
          order_by: 'due_date',
          order_by_desc: 1,
          is_completed:
            this.filtersImportantAndCompleted.is_completed || undefined,
          is_important:
            this.filtersImportantAndCompleted.is_important || undefined,
          tag_id: this.filtersImportantAndCompleted.tags || undefined,
        })
        .then((data) => {
          that.$vs.loading.close();
        })
        .catch((err) => {
          that.$vs.loading.close();
          console.error(err);
        });
    },
    fetchTag() {
      const that = this;
      this.$vs.loading();
      this.$store
        .dispatch("tagManagement/fetchItems", {
          q: this.searchQuery || undefined,
        })
        .then((data) => {
          that.$vs.loading.close();
        })
        .catch((err) => {
          console.error(err);
        });
    },
    deleteRecord() {
      this.$store
        .dispatch("tagManagement/forceRemoveItems", [this.itemToDel.id])
        .then(() => {
          this.showDeleteSuccess();
        })
        .catch((err) => {
          console.error(err);
        });

      this.itemToDel = null;
    },
    showDeleteSuccess() {
      this.$vs.notify({
        color: "danger",
        title: "Tâche",
        text: "La tâche est supprimée",
      });
    },
    moveToTrash(item) {
      this.itemToDel = item;
      this.$vs.dialog({
        type: "confirm",
        color: "danger",
        title: "Confirmer suppression",
        text: `Voulez vous vraiment supprimer la tâche "${item.title}"`,
        accept: this.deleteRecord,
        acceptText: "Supprimer",
        cancelText: "Annuler",
      });
    },
    updateSearchQuery(val) {
      this.fetchTodo();
    },
    resetSortAndNavigate() {
      const currentRouteQuery = JSON.parse(JSON.stringify(route.value.query));

      delete currentRouteQuery.sort;

      return router
        .replace({ name: route.name, query: currentRouteQuery })
        .catch(() => {});
    },
    showDisplayPrompt(itemId) {
      this.taskIdToEdit = itemId.id;
      this.displayPrompt = true;
    },
      showDisplayPromptTag(itemId) {
        
      this.tagIdToEdit = itemId.id;
      this.displayPromptTag = true;
    },
    hidePrompt() {
      this.displayPrompt = false;
      this.displayPromptTag = false;

    },
    setSidebarWidth() {
      if (this.windowWidth < 992) {
        this.isSidebarActive = this.clickNotClose = false;
      } else {
        this.isSidebarActive = this.clickNotClose = true;
      }
    },
  },
  components: {
    TagEdit,
    TodoAddNew,
    TodoTask,
    TodoEdit,
    VuePerfectScrollbar,
    SimpleSelect,
    TodoAddTags,
    moment,
    draggable,
    FrenchLocale,
    flatPickr
  },
  created() {
    if (!moduleTodo.isRegistered) {
      this.$store.registerModule("todoManagement", moduleTodo);
      moduleTodo.isRegistered = true;
    }
    if (!moduleTag.isRegistered) {
      this.$store.registerModule("tagManagement", moduleTag);
      moduleTag.isRegistered = true;
    }

    this.fetchTodo();
    this.fetchTag();
  },
  mounted() {},
};
</script>


<style lang="scss">
@import "@sass/vuexy/apps/todo.scss";

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
.task_editor__editing_area {
border: 1px solid #ddd;
  border-radius: 5px;
  padding: 10px 10px 10px;
  margin: 20px;
}
$colors: (
  "51E898": #51e898,
  "61BD4F": #61bd4f,
  "F2D600": #f2d600,
  "FF9F1A": #ff9f1a,
  "EB5A46": #eb5a46,
  "FF78CB": #ff78cb,
  "C377E0": #c377e0,
  "00C2E0": #00c2e0,
  "0079BF": #0079bf,
  "344563": #344563,
);
</style>
