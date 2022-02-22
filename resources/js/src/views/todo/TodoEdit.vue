<!-- =========================================================================================
    File Name: TodoEdit.vue
    Description: Edit todo component
    ----------------------------------------------------------------------------------------
    Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
      Author: Pixinvent
    Author URL: http://www.themeforest.net/user/pixinvent
========================================================================================== -->


<template>
  <vs-prompt
    title="Modifier la tâche"
    accept-text="Modifier"
    cancel-text="Annuler"
    button-cancel="border"
    @cancel="clear"
    @accept="submitItem"
    @close="clear"
    :is-valid="validateForm"
    :active.sync="activePrompt"
  >
    <div>
      <form>
        <div class="vx-row">
          <div class="vx-col w-1/6 self-center">
            <vs-checkbox v-model="itemLocal.is_completed" class="w-8"></vs-checkbox>
          </div>

          <div class="vx-col ml-auto flex" >
            <feather-icon
              icon="StarIcon"
              class="cursor-pointer"
              style=""
              
              :svgClasses="[
                { 'text-warning stroke-current': itemLocal.is_important },
                'w-5',
                'h-5 mr-4',
              ]"
              @click.prevent="itemLocal.is_important = !itemLocal.is_important"
            />
          </div>
        </div>
        <div class="vx-row">
          <div class="vx-col w-full"           >
            <vs-input
              v-validate="'max:200|required'"
              name="title"
              class="w-full mb-4 mt-5"
              placeholder="Title"
              v-model="itemLocal.title"
              :success="itemLocal.title != '' && !errors.has('title')"
              :danger="errors.has('title')"
              :danger-text="errors.first('title')"   
             :disabled="(itemLocal.is_completed == true)"
            />
               <simple-select
                required
                v-model="itemLocal.tags"
                multiple
                label="title"
                header="Tags"
                name="tags"
                :close-on-select="false"  
                :options="taskTags"
                :reduce="item => item.id"
                input-id="tags"
                :disabled="(itemLocal.is_completed == true)"
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
              class="mt-5"
              name="description"
              label="Ajouter une description"
              v-model="itemLocal.description"
            />
          </div>
        </div>
      </form>
    </div>
  </vs-prompt>
</template>

<script>
import SimpleSelect from "../../components/inputs/selects/SimpleSelect.vue";
import flatPickr from "vue-flatpickr-component";
import { French as FrenchLocale } from "flatpickr/dist/l10n/fr.js";
import "flatpickr/dist/flatpickr.css";
export default {
  components:{
    SimpleSelect,
    flatPickr,
    FrenchLocale
  },
  props: {
    displayPrompt: {
      type: Boolean,
      required: true,
    },
    taskId: {
      type:Number,
      required: true,
    },
  },
  
  data() {
   const storeItem = JSON.parse(
            JSON.stringify(
                this.$store.getters["todoManagement/getItem"](this.taskId)
            )
        );
        storeItem.tags = storeItem.tags.map(tag => tag.id);
    return {
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
        maxDate: null,
      },
     itemLocal: storeItem,
    };
  },
  
  computed: {
    taskTags() {
      return this.$store.getters["tagManagement/getItems"];
    },
    activePrompt: {
      get() {
        return this.displayPrompt;
      },
      set(value) {
        this.$emit("hideDisplayPrompt", value);
      },
    },
    validateForm() {
      return !this.errors.any() && this.itemLocal.title !== ""
       && this.itemLocal.due_date !== "";
    },
  },
  methods: {
    
      clear () {
       this.itemLocal={
        };
    },
    submitItem() {
            this.$validator.validateAll().then(result => {
                const item = JSON.parse(JSON.stringify(this.itemLocal));
                this.$store
                    .dispatch("todoManagement/updateItem", item)
                    .then(data => {
                        if (this.refreshData) {
                            this.refreshData();
                        }
                        this.$vs.loading.close();
                        if(this.itemLocal.is_completed == 1)
                        {
                           this.$vs.notify({
                            title: "Finalisation d'une tâche",
                            text: `"${this.itemLocal.title}" est finalisé avec succès`,
                            iconPack: "feather",
                            icon: "icon-alert-circle",
                            color: "success"
                        }); 
                        }
                        else{
                           this.$vs.notify({
                            title: "Modification d'un projet",
                            text: `"${this.itemLocal.title}" modifiée avec succès`,
                            iconPack: "feather",
                            icon: "icon-alert-circle",
                            color: "success"
                        });
                        }
                       
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
            });
        },
  },
};
</script>
