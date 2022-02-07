<!-- =========================================================================================
    File Name: TodoItem.vue
    Description: Single todo item component
    ----------------------------------------------------------------------------------------
    Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
      Author: Pixinvent
    Author URL: http://www.themeforest.net/user/pixinvent
========================================================================================== -->


<template>
  <div @click="displayPrompt" class="px-4 py-1 list-item-component">
    <div class="vx-row py-4">
        
      <div
        class="vx-col w-full sm:w-5/6 flex sm:items-center sm:flex-row flex-col"
      >
        <div class="flex items-center">
          <vs-checkbox
            v-model="isCompleted"
            :checked="task_data.is_completed"
            class="w-8 m-0 vs-checkbox-medium"
            @click.native.stop
          />
          <h6
            class="todo-title"
            :class="{ 'line-through': task_data.is_completed }"
          >
            {{ capitalize(task_data.title) }}
          </h6>
        </div>
      </div>

      <div class="vx-col w-full sm:w-1/6 ml-auto flex sm:justify-end">
        <div class="todo-tags flex mr-10">
          <vs-chip
            v-for="(tag, index) in task_data.tags"
            :key="index"
            class="ml-4 mt-1"
            :style="` background-color: ${tag.color};`"
          >
            <span :style="`color: white; font-size: 12px; font-weight: 600;`">{{
              capitalize(tag.title)
            }}</span>
          </vs-chip>
        </div>
        <h6
          class="mr-2  mt-2"
          style="color: grey; font-size: 14px; min-width: 60px"
        >
          {{ format_date(task_data.due_date) }}
        </h6>
  <feather-icon
          v-model="isImportant"
          icon="StarIcon"
          class="cursor-pointer mr-2"
          :style="{'color': task_data.is_important == true ? '#ffc107' : '#1b263b' }"
          svgClasses="w-5 h-5"
          @click="task_data.is_important"

        />
        <feather-icon
          v-if="!task_data.isTrashed"
          icon="TrashIcon"
          class="cursor-pointer"
          style="color:#ba181b;"
          svgClasses="w-5 h-5"
          @click.stop="moveToTrash(task_data)"
        />
      </div>
    </div>
    <div class="vx-row" v-if="task_data.desc">
      <div class="vx-col w-full">
        <p
          class="mt-2 truncate"
          :class="{ 'line-through': task_data.isCompleted }"
        >
          {{ task_data.desc }}
        </p>

      </div>
    </div>    
  </div>
</template>

<script>
import moment from "moment";
import draggable from "vuedraggable";

moment.locale("fr");
export default {
  components: {
    draggable,
    moment,
  },
  props: {
    task_data: {
      required: true,
    },
  },
  data() {  
return {
      itemToDel: null,
    };
  },
  computed: {
    isCompleted: {
      get() {
        return this.task_data.is_completed;
      },
      set(value) {
        this.task_data.tags = this.task_data.tags.map(tag => tag.id);
        this.task_data.is_completed = value;

        this.$store
          .dispatch(
            "todoManagement/updateItem",
           this.task_data)

          .then((data) => {
          })
          .catch((error) => {
            console.error(error);
          });
      },
    },
     isImportant: {
      get() {
        return this.task_data.is_important;
      },
      set(value) {
        this.task_data.tags = this.task_data.tags.map(tag => tag.id);
        this.task_data.is_important = value;

        this.$store
          .dispatch(
            "todoManagement/updateItem",
           this.task_data)

          .then((data) => {
          })
          .catch((error) => {
            console.error(error);
          });
      },
    },
  },
  methods: {
    capitalize(value) {
      return value[0].toUpperCase() + value.slice(1);
    },
    format_date(value) {
      if (value) {
        return moment(value).format("DD MMM ");
      }
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
    deleteRecord() {
      this.$store
        .dispatch("todoManagement/forceRemoveItems", [this.itemToDel.id])
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
    displayPrompt() {
      this.$emit("showDisplayPrompt", this.task_data);
    },
  },
  created() {},
};
</script>
<style lang="scss" scoped>
.draggable-task-handle {
  position: absolute;
  top: 50%;
  left: 10px;
  transform: translateY(-50%);
  visibility: hidden;
  cursor: move;

  .todo-list .todo_todo-item:hover & {
    visibility: visible;
  }
}
</style>
