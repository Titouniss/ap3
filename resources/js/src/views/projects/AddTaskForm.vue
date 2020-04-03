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
          :active.sync="activePrompt">
          <div>
              <form>
                  <div class="vx-row">
                      <div class="vx-col w-full">
                        <vs-input v-validate="'required'" name="name" class="w-full mb-4 mt-5" placeholder="Nom" v-model="itemLocal.name" :color="validateForm ? 'success' : 'danger'" />
                         <ul style="display: flex">
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
                        <div class="my-4">
                          <small class="date-label mb-1" style="display: block;">Date</small>
                          <flat-pickr :config="configdateTimePicker" v-model="itemLocal.date" placeholder="Date" />
                        </div>
                        <div class="my-4">
                          <small class="date-label">Temps estimé (en h)</small>
                          <vs-input name="estimatedTime" type="number" class="w-full mb-4 mt-1" v-model="itemLocal.estimated_time" 
                            :color="validateForm ? 'success' : 'danger'" placeholder="Saisir une durée"/>
                        </div>
                        <div class="my-4" v-if="itemLocal.status == 'done'">
                          <small class="date-label">Temps passé (en h)</small>
                          <vs-input name="timeSpent" type="number" class="w-full mb-4 mt-1" v-model="itemLocal.time_spent" 
                            :color="validateForm ? 'success' : 'danger'" placeholder="Saisir une durée" />
                        </div>
                        <vs-select name="workarea" v-validate="'required'" label="Ilot" v-model="itemLocal.workarea_id" class="w-full mt-5">
                            <vs-select-item :key="index" :value="item.id" :text="item.name" v-for="(item,index) in workareasData" />
                        </vs-select>
                        <div class="vx-row mt-4" v-if="!disabled">
                          <div class="vx-col w-full">
                            <div class="flex items-end px-3">
                                <feather-icon svgClasses="w-6 h-6" icon="LockIcon" class="mr-2" />
                                <span class="font-medium text-lg leading-none">Admin</span>
                            </div>
                            <vs-divider />
                            <!-- <vs-select name="company" v-validate="'required'" label="Compagnie" v-model="itemLocal.created_by" class="w-full mt-5">
                              <vs-select-item :key="index" :value="item.id" :text="item.name" v-for="(item,index) in usersData" />
                            </vs-select> -->
                          </div>
                        </div>
                      </div>
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
        project_id: this.project_data.id
      }
    }
  },
  computed: {
    validateForm () {
      return !this.errors.any()
    },
    workareasData() {
      let $workareasDataFilter = []
      let $workareasData = this.$store.state.workareaManagement.workareas
      const user = this.$store.state.AppActiveUser 
      if (user.roles && user.roles.length > 0) {
        if (user.roles.find(r => r.name === 'superAdmin' || r.name === 'littleAdmin')) {
          $workareasDataFilter = $workareasData.filter((item) => item.company_id === this.project_data.company_id)
        }
        else{
          $workareasDataFilter = $workareasData
        }
        $workareasDataFilter.splice(0, 0, {id: 'null', name:'Aucun'})
      }
      return $workareasDataFilter
    },
    // usersData() {
    //   return this.$store.state.usersManagement.users
    // },
    disabled () { 
      const user = this.$store.state.AppActiveUser 
      if (user.roles && user.roles.length > 0) {
        if (user.roles.find(r => r.name === 'superAdmin' || r.name === 'littleAdmin')) {
          return false
        } else  {
          this.itemLocal.company_id = user.company_id
          return true
        }
      } else return true
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
        project_id: this.project_data.id
      })
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
    }
  }
}
</script>
