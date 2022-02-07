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
              style="color: gray; font-weight: 500"
              class="flex cursor-pointer"
              type="transparent"
              @click="filtersImportantAndCompleted.is_completed = 0"
              v-on:click="filtersImportantAndCompleted.is_important = 0"
              v-on:click.stop="filtersImportantAndCompleted.tags =null"
            >
              <feather-icon
                icon="LayersIcon"
                clas
                :svgClasses="['h-6 w-6']"
              ></feather-icon>
              <span class="text-lg ml-3">Tous</span>
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
              >
                <feather-icon
                  icon="CheckIcon"
                  :svgClasses="['h-6 w-6']"
                ></feather-icon>
                <span class="text-lg ml-3">Complété</span>
              </a>

              <a
                style="color: gray; font-weight: 500"
                class="flex mt-6 cursor-pointer"
                type="transparent"
                @click="filtersImportantAndCompleted.is_important = 1"
                v-on:click="filtersImportantAndCompleted.is_completed = 0"
              >
                <feather-icon
                  icon="StarIcon"
                  :svgClasses="['h-6 w-6']"
                ></feather-icon>
                <span class="text-lg ml-3">Important</span>
              </a>
            </template>
          </div>

          <vs-divider></vs-divider>
          <div class="w-full px-6 py-2">
            <todo-add-tags tag_list="tagData" />

            <h5>Tags</h5>
            <div class="todo__lables-list">
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
                    icon="TrashIcon"
                    class="cursor-pointer"
                    svgClasses="w-5 h-5"
                    style="color: red"
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
          icon="icon-search"
          placeholder="Search..."
          v-model="searchQuery"
          @input="updateSearchQuery"
          class="vs-input-no-border vs-input-no-shdow-focus w-full"
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
        <!-- Toute les tâches  -->
          <h3 class="m-5">En retard, les dernières semaines</h3>
          <draggable
            tag="ul"
            :list="tasksBeforeYesterday"
            group="todo"
            :move="onMoveCallback"
            class="beforeYesterday"
          >
            <li
              v-for="(task, index) in tasksBeforeYesterday.slice(0, 7)"
              :key="String(task.id)"
              :style="[{ transitionDelay: index * 0.1 + 's' }]"
            >
              <todo-task
                :task_data="task"
                @showDisplayPrompt="showDisplayPrompt(task)"
                :key="String(task.title)"
              />
            </li>
          </draggable>
   

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
            >
              <todo-task
                :task_data="task"
                @showDisplayPrompt="showDisplayPrompt(task)"
                :key="String(task.title)"
              />
            </li>
          </draggable>


          <h3 class="m-5">Aujourd'hui</h3>
          <draggable
            tag="ul"
            :list="tasksToday"
            group="todo"
            :move="onMoveCallback"
            class="today">
          
            <li
              v-for="(task, index) in tasksToday"
              :key="String(task.id)"
              :style="[{ transitionDelay: index * 0.1 + 's' }]"
            >
              <todo-task
                :task_data="task"
                @showDisplayPrompt="showDisplayPrompt(task)"
                :key="String(task.title)"
              />
            </li>
          </draggable>


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
            >
              <todo-task
                :task_data="task"
                @showDisplayPrompt="showDisplayPrompt(task)"
                :key="String(task.title)"
              />
            </li>
          </draggable>


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
            >
              <todo-task
                :task_data="task"
                @showDisplayPrompt="showDisplayPrompt(task)"
                :key="String(task.title)"
              />
            </li>
          </draggable>
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
  </div>
</template>

<script>
import moduleTodo from "@/store/todo-management/moduleTodoManagement.js";
import moduleTag from "@/store/tag-management/moduleTagManagement.js";
import TodoAddNew from "./TodoAddNew.vue";
import TodoTask from "./TodoTask.vue";
import TodoEdit from "./TodoEdit.vue";
import TodoAddTags from "./TodoAddTags.vue";
import VuePerfectScrollbar from "vue-perfect-scrollbar";
import SimpleSelect from "@/components/inputs/selects/SimpleSelect.vue";
import moment from "moment";
import draggable from "vuedraggable";

export default {
  data() {
    return {
      props: {},
      itemToDel: null,
      searchQuery: "",
      clickNotClose: true,
      displayPrompt: false,
      taskIdToEdit: 0,
      isSidebarActive: true,
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
        );
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
        moment(x.due_date).isSame(
          moment().subtract(1, "days").format("YYYY-MM-DD")
        )
      );
    },
    tasksBeforeYesterday() {
        return this.todoData.filter((x) =>
        moment(x.due_date).isBefore(
          moment().subtract(1, "days").format("YYYY-MM-DD")
        )
      );      
    },

    tagData() {
      return this.$store.getters["tagManagement/getItems"];
    },
    // searchQuery:   {
    //   get ()        { return this.$store.state.todo.todoSearchQuery        },
    //   set (val)     { this.$store.dispatch('todoManagement/setTodoSearchQuery', val) }
    // },
    scrollbarTag() {
      return this.$store.getters.scrollbarTag;
    },
    windowWidth() {
      return this.$store.state.windowWidth;
    },
  },
  methods: {
    onMoveCallback(evt, originalEvent) {

    switch (evt.to.className) {
        case "beforeYesterday":
          evt.draggedContext.element.due_date = moment()
            .subtract(2, "days")
            .format("YYYY-MM-DD");
          break;
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
      if(evt.draggedContext.element != null)
      {
        let todo = {
        id: evt.draggedContext.element.id,
        title: evt.draggedContext.element.title,
        is_completed: evt.draggedContext.element.is_completed,
        is_important: evt.draggedContext.element.is_important,
        due_date: evt.draggedContext.element.due_date,
        description: evt.draggedContext.element.description,
      };
      evt.dragged.parentNode.removeChild(evt.dragged)
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
            this.$vs.notify({
            color: "danger",
            title: "Erreur",
            text: `Ce tag n'est pas attribué à une tâche.`,
            });
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
        color: "success",
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
      this.fetchTag();
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
    hidePrompt() {
      this.displayPrompt = false;
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
    TodoAddNew,
    TodoTask,
    TodoEdit,
    VuePerfectScrollbar,
    SimpleSelect,
    TodoAddTags,
    moment,
    draggable,
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

.draggable-task-handle {
  position: absolute;
  left: 8px;
  top: 50%;
  transform: translateY(-50%);
  visibility: hidden;
  cursor: move;

  .todo-task-list .todo-item:hover & {
    visibility: visible;
  }
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
