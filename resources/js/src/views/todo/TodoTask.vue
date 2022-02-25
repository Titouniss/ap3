<!-- =========================================================================================
    File Name: TodoItem.vue
    Description: Single todo item component
    ----------------------------------------------------------------------------------------
    Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
      Author: Pixinvent
    Author URL: http://www.themeforest.net/user/pixinvent
========================================================================================== -->


<template>
  <div @click="displayPrompt" class="py-1 list-item-component">
    <div class="flex todo-title-wrapper">


      <div class=" sm:items-center todo-title-area" >
        <div class="flex" >
          <vs-checkbox
            v-model="isCompleted"
            :checked="task_data.is_completed"
            class="w-8 ml-5 vs-checkbox-medium"
            @click.native.stop
          />
          <h6
            class="title-wrapper mb-5 ml-2 "
            style="white-space: pre-line;"
            :class="{ 'line-through': task_data.is_completed }"
          >
            {{ capitalize(task_data.title) }}
          </h6>
        </div>
      </div>

      <div class=" ml-auto todo-item-action">
        <div class="title-wrapper mr-3">
          <vs-chip
            v-for="(tag, index) in task_data.tags"
            :key="index"
            class="ml-4 mt-3"
            :style="` background-color: ${tag.color};`"
          >
            <span :style="`color: white; font-size: 12px; font-weight: 600;`">{{
              capitalize(tag.title)
            }}</span>
          </vs-chip>
        </div>
        <h6
          class="mr-2"
          style="color: grey; font-size: 14px; min-width: 60px"
        >
          {{ format_date(task_data.due_date) }}
        </h6>
        <feather-icon
          v-model="isImportant"
          icon="StarIcon"
          class="cursor-pointer mr-2"
          :style="{
            color: task_data.is_important == true ? '#ffc107' : '#1b263b',
          }"
          svgClasses="w-5 h-5"
          @click="task_data.is_important"
        />
        <feather-icon
          v-if="!task_data.isTrashed"
          icon="Trash2Icon"
          class="cursor-pointer hover:text-danger"
          svgClasses="w-5 h-5 mr-2"
          @click.stop="moveToTrash(task_data)"
        />
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
        this.task_data.tags = this.task_data.tags.map((tag) => tag.id);
        this.task_data.is_completed = value;

        this.$store
          .dispatch("todoManagement/updateItem", this.task_data)

          .then((data) => {
            if (this.task_data.is_completed == 1) {
              this.$vs.notify({
                title: "Finalisation d'une tâche",
                text: `"${this.task_data.title}" est finalisé avec succès`,
                iconPack: "feather",
                icon: "icon-alert-circle",
                color: "success",
              });
            }
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
        this.task_data.tags = this.task_data.tags.map((tag) => tag.id);
        this.task_data.is_important = value;

        this.$store
          .dispatch("todoManagement/updateItem", this.task_data)

          .then((data) => {})
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
        color: "danger",
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
      
    .todo-title-wrapper {
        .title-wrapper {
          margin-bottom: 0.5rem;
        }
        .todo-title {
          display: -webkit-box;
          -webkit-line-clamp: 1;
          -webkit-box-orient: vertical;
          overflow: hidden;
        }
        .badge-wrapper {
          margin-right: auto !important;
        }
      }
.todo-item-action {
  display: flex;
  align-items: center;
  > small {
    margin-left: auto;
  }

  a {
    cursor: pointer;
    font-size: 1.2rem;
    line-height: 1.5;
  }
}
.truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
