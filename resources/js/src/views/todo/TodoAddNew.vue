<!-- =========================================================================================
    File Name: TodoAddNew.vue
    Description: Add new todo component
    ----------------------------------------------------------------------------------------
    Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
      Author: Pixinvent
    Author URL: http://www.themeforest.net/user/pixinvent
========================================================================================== -->


<template>
  <div class="px-6 pb-2 pt-6">
    <vs-button @click="activePrompt = true" class="w-full"
      >Ajouter une tâche</vs-button
    >
    <vs-prompt
      title="Ajouter une tâche"
      accept-text="Ajouter"
      cancel-text="Annuler"
      button-cancel="border"
      @cancel="clearFields"
      @accept="addTodo"
      @close="clearFields"
      :is-valid="validateForm"
      :active.sync="activePrompt"
    >
      <div>
        <form  autocomplete="off" v-on:submit.prevent>
          <div class="vx-row">
            <div class="vx-col ml-auto flex">
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
          </div>

          <div class="vx-row">
            <div class="vx-col w-full">
              <vs-input
                v-validate="'required'"
                label="Titre"
                name="title"
                class="w-full mb-4 mt-5"
                placeholder="Title"
                v-model="itemLocal.title"
                :color="validateForm ? 'success' : 'danger'"
              />

              <simple-select
                v-model="itemLocal.tags"
                multiple
                label="title"
                header="Tags"
                name="tags"
                :close-on-select="false"  
                :options="taskTags"
                :reduce="item => item.id"
                input-id="tags"
              />
              <small class="date-label pl-2 mt-5" style="display: block">
                Date
              </small>
              <flat-pickr
                v-validate="{
                  required: true,
                }"
                v-model="itemLocal.due_date"
                class="w-full"
                header="Date d'embauche"
                name="due_date"
                :config="configdateTimePicker"
              />
              <vs-textarea
                rows="5"
                label="Ajout d'une description"
                v-model="itemLocal.description"
                name="description"
                class="mt-5"
              />
            </div>
          </div>
        </form>
      </div>
    </vs-prompt>
  </div>
</template>

<script>
import SimpleSelect from "../../components/inputs/selects/SimpleSelect.vue";
import flatPickr from "vue-flatpickr-component";
import moment from "moment";
import { French as FrenchLocale } from "flatpickr/dist/l10n/fr.js";
import "flatpickr/dist/flatpickr.css";

export default {
  components: { SimpleSelect, flatPickr, FrenchLocale },

  data() {
    return {
      props:{
      todo_lists: { required: true },
      tagLocal:{}
      },
      activePrompt: false,
      itemLocal: {
        title: "",
        description: "",
        due_date: new Date(),
        is_completed: false,
        is_important: false,
        tags:[],
      },
      configdateTimePicker: {
        disableMobile: "true",
        locale: FrenchLocale,
        // minDate: new Date(Date.now() - 8640000),
        maxDate: null,
      },
      isSubmiting: false,
    };
  },
  computed: {
     isAdmin() {
            return this.$store.state.AppActiveUser.is_admin;
        },
    taskTags() {
      return this.$store.getters["tagManagement/getItems"];
    },
    validateForm() {
      return !this.errors.any() 
      && this.itemLocal.title !== ""
      && this.itemLocal.due_date !=="";
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
    },
    addTodo() {
       if (!this.validateForm || this.isSubmiting) return;

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
  },
};
</script>
