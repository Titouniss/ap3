<template>
    <div class="p-3 mb-4 mr-4">
      <vs-button @click="activePrompt = true" class="w-full">
          Ajouter une tâche
      </vs-button>
      <vs-prompt
          title="Ajouter une tâche"
          accept-text= "Ajouter"
          cancel-text= "Annuler"
          button-cancel = "border"
          @cancel="clearFields"
          @accept="addItem"
          @close="clearFields"
          :is-valid="validateForm"
          :active.sync="activePrompt"
          class="task-compose">
          <div>
              <form>
                <div class="vx-row">
                  <!-- Left -->
                  <div class="vx-col flex-1" style="border-right: 1px solid #d6d6d6;">
                    <vs-input v-validate="'required'" name="name" class="w-full mb-4 mt-1" placeholder="Nom" v-model="itemLocal.name" :color="validateForm ? 'success' : 'danger'" />
                    <div class="my-3">
                      <small class="date-label mb-1" style="display: block;">Date</small>
                      <flat-pickr :config="configdateTimePicker" v-model="itemLocal.date" placeholder="Date" class="w-full"/>
                    </div>
                    <vs-select label="Compétences" v-on:change="updateWorkareasList" v-model="itemLocal.skills" class="w-full mt-5" multiple autocomplete>
                      <vs-select-item :key="index" :value="item.id" :text="item.name" v-for="(item,index) in skillsData" />
                    </vs-select>
                    <div v-if="itemLocal.skills.length > 0 && workareasDataFiltered.length > 0">
                      <vs-select name="workarea" label="Ilot" v-model="itemLocal.workarea_id" class="w-full mt-3">
                          <vs-select-item :key="index" :value="item.id" :text="item.name" v-for="(item,index) in workareasDataFiltered" />
                      </vs-select>
                    </div>
                  </div>
                  <!-- Right -->
                  <div class="vx-col flex-1">
                    <ul style="display: flex" class="mt-3">
                      <li class="mr-3">
                        <vs-radio color="danger" v-model="itemLocal.status" vs-value="todo">A faire</vs-radio>
                      </li>
                      <li class="mr-3">
                        <vs-radio color="warning" v-model="itemLocal.status" vs-value="doing">En cours</vs-radio>
                      </li>
                      <li>
                        <vs-radio color="success" v-model="itemLocal.status" vs-value="done">Terminé</vs-radio>
                      </li>
                    </ul>
                    <div class="my-4 mt-5 mb-2">
                      <small class="date-label">Temps estimé (en h)</small>
                      <vs-input name="estimatedTime" type="number" class="w-full mb-2 mt-1" v-model="itemLocal.estimated_time" 
                        :color="validateForm ? 'success' : 'danger'" placeholder="Saisir une durée"/>
                    </div>
                    <div class="my-4 mt-0 mb-0" v-if="itemLocal.status == 'done'">
                      <small class="date-label">Temps passé (en h)</small>
                      <vs-input name="timeSpent" type="number" class="w-full mb-0 mt-1" v-model="itemLocal.time_spent" 
                        :color="validateForm ? 'success' : 'danger'" placeholder="Saisir une durée" />
                    </div>
                  </div>
                </div>
                <div class="my-4">
                  <small class="date-label">Commentaires</small>
                  <vs-textarea rows="2" label="Ajouter un commentaire" name="comment" class="w-full mb-1 mt-1" v-model="itemLocal.comment" 
                    :color="validateForm ? 'success' : 'danger'"/>
                </div>
              </form>
          </div>
      </vs-prompt>
    </div>
</template>

<script>
import flatPickr from 'vue-flatpickr-component';
import 'flatpickr/dist/flatpickr.css';
import {French as FrenchLocale} from 'flatpickr/dist/l10n/fr.js';
import moment from 'moment'

export default {
  components: {
    flatPickr
  },
  props: {
    project_data: {
      required: true
    }
  },
  data () {
    return {
      activePrompt: false,
      configdateTimePicker: {
        disableMobile: "true",
        enableTime: true,
        dateFormat: 'd-m-Y H:i',
        locale: FrenchLocale
      },

      itemLocal: {
        name: '',
        date: new Date(),
        estimated_time: '',
        time_spent: '',
        task_bundle_id: null,
        workarea_id: 'null',
        created_by: '',
        status: 'todo',
        project_id: this.project_data.id,
        comment: '',
        skills: [],
      },

      workareasDataFiltered: [],
      comments: []
    }
  },
  computed: {
    validateForm () {
      return !this.errors.any()
    },
    workareasData() {
      let $workareasData = this.$store.state.workareaManagement.workareas
      let $filteredItems = this.filterItemsAdmin($workareasData)
      return $filteredItems
    },
    skillsData() {
      let $skillsData = this.$store.state.skillManagement.skills
      return this.filterItemsAdmin($skillsData)
    },
  },
  methods: {
    clearFields () {
      Object.assign(this.itemLocal, {
        name: '',
        date: new Date(),
        estimated_time: '',
        time_spent: '',
        task_bundle_id: null,
        workarea_id: 'null',
        created_by: '',
        status: 'todo',
        skills: [],
        project_id: this.project_data.id,
        comment: ''
      })
      Object.assign(this.workareasDataFiltered, [])
    },
    addItem () {
      this.$validator.validateAll().then(result => {

        this.itemLocal.date = moment(this.itemLocal.date).format('YYYY-MM-DD HH:mm')
        this.itemLocal.workarea_id = this.itemLocal.workarea_id == 'null' ? null : this.itemLocal.workarea_id

        if (result) {
          this.$store.dispatch('taskManagement/addItem', Object.assign({}, this.itemLocal))
          .then(() => { 
            this.$vs.loading.close() 
            this.$vs.notify({
              title: 'Ajout d\'une tâche',
              text: `"${this.itemLocal.name}" ajouté avec succès`,
              iconPack: 'feather',
              icon: 'icon-alert-circle',
              color: 'success'
            })
            this.clearFields()
          })
          .catch(error => {            
            this.$vs.loading.close()
            this.$vs.notify({
              title: 'Error',
              text: error.message,
              iconPack: 'feather',
              icon: 'icon-alert-circle',
              color: 'danger'
            })
          })
        }
      })
    },
    updateWorkareasList(ids) {
      this.workareasDataFiltered = this.workareasData.filter(function(workarea) {
        for (let i = 0; i < ids.length; i ++) {
          if(workarea.skills.filter(skill => skill.id == ids[i]).length == 0){
            return false;
          }
        }
        return true;
      });
    },
    filterItemsAdmin ($items) {
      let $filteredItems = []
      const user = this.$store.state.AppActiveUser 
      if (user.roles && user.roles.length > 0) {
        if (user.roles.find(r => r.name === 'superAdmin' || r.name === 'littleAdmin')) {
          $filteredItems = $items.filter((item) => item.company_id === this.project_data.company_id)
        }
        else{
          $filteredItems = $items
        }
      }
      return $filteredItems
    }
  }
}
</script>
<style>
.con-vs-dialog.task-compose .vs-dialog{
  max-width: 700px
}
</style>
