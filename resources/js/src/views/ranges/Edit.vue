<!-- =========================================================================================
  File Name: RoleEdit.vue
  Description: range Edit Page
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/range/pixinvent
========================================================================================== -->

<template>
  <div id="page-range-edit">
    <vs-alert color="danger" title="range Not Found" :active.sync="range_not_found">
      <span>La gamme {{ $route.params.id }} est introuvable. </span>
      <span>
        <span>voir </span><router-link :to="{name:'ranges'}" class="text-inherit underline">Toutes les games</router-link>
      </span>
    </vs-alert>

    <vx-card v-if="range_data">
      <div slot="no-body" class="tabs-container px-6 pt-6">
        <div class="vx-row">
          <vs-input class="w-full mt-4" label="Titre" v-model="range_data.name" v-validate="'required'" name="name" />
          <span class="text-danger text-sm"  v-show="errors.has('name')">{{ errors.first('name') }}</span>
        </div>
        <div class="vx-row">
          <vs-textarea class="w-full mt-4" rows="5" label="Ajouter description" v-model="range_data.description" name="description"/>
          <span class="text-danger text-sm"  v-show="errors.has('description')">{{ errors.first('description') }}</span>
        </div>
        <!-- Permissions -->
        <div class="vx-row mt-4">
            <div class="vx-col w-full">
              <div class="flex items-end px-3">
                <feather-icon svgClasses="w-6 h-6" icon="PackageIcon" class="mr-2" />
                <span class="font-medium text-lg leading-none">Liste des étapes de la gamme</span>
                <add-form v-if="range_data.company_id != null" :company_id="range_data.company_id"></add-form>
              </div>
              <vs-divider />
              <vs-table :data="repetitiveTasksData">

                <template slot="thead">
                  <vs-th>Ordre</vs-th>
                  <vs-th>Intitulé</vs-th>
                  <vs-th>Ilot</vs-th>
                  <vs-th>Temps</vs-th>
                  <vs-th></vs-th>
                </template>

                <template slot-scope="{data}">
                  <vs-tr :key="indextr" v-for="(tr, indextr) in data">

                    <vs-td :data="data[indextr].order">
                      {{ data[indextr].order }}
                    </vs-td>

                    <vs-td :data="data[indextr].name">
                      {{ data[indextr].name }}
                    </vs-td>

                    <vs-td :data="data[indextr].workarea">
                      {{ data[indextr].workarea ? data[indextr].workarea.name : 'Aucun' }}
                    </vs-td>

                    <vs-td :data="data[indextr].estimated_time">
                       {{ data[indextr].estimated_time }}
                    </vs-td>

                    <vs-td :data="data[indextr]">
                      <CellRendererActions :item="data[indextr]"></CellRendererActions>
                    </vs-td>
                  </vs-tr>
                </template>
              </vs-table>
            </div>
        </div>
      </div>
      <!-- Save & Reset Button -->
      <div class="vx-row">
        <div class="vx-col w-full">
          <div class="mt-8 flex flex-wrap items-center justify-end">
            <vs-button class="ml-auto mt-2" @click="save_changes" :disabled="!validateForm">Modifier</vs-button>
            <vs-button class="ml-4 mt-2" type="border" color="warning" @click="back">Annuler</vs-button>
          </div>
        </div>
      </div>
    </vx-card>
    <edit-form :itemId="itemIdToEdit" :companyId="range_data.company_id" v-if="itemIdToEdit"/>
  </div>
</template>

<script>
import lodash from 'lodash'

//Repetitive Task
import AddForm from './repetitives-tasks/AddForm.vue'
import EditForm from './repetitives-tasks/EditForm.vue'
import CellRendererActions from './repetitives-tasks/cell-renderer/CellRendererActions.vue'

// Store Module
import moduleRangeManagement from '@/store/range-management/moduleRangeManagement.js'
import moduleCompanyManagement from '@/store/company-management/moduleCompanyManagement.js'
import moduleWorkareaManagement from '@/store/workarea-management/moduleWorkareaManagement.js'
import moduleSkillManagement from '@/store/skill-management/moduleSkillManagement.js'
import moduleRepetitiveTaskManagement from '@/store/repetitives-task-management/moduleRepetitiveTaskManagement.js'

var model = 'range'
var modelPlurial = 'ranges'
var modelTitle = 'Gamme'

export default {
  components: {
    AddForm,
    EditForm,
    CellRendererActions
  },
  data () {
    return {
      range_data: null,
      selected: [],
      range_not_found: false,
    }
  },
  computed: {
    repetitiveTasksData() {
      return this.$store.state.repetitiveTaskManagement.repetitivesTasks
    },
    validateForm () {
      return !this.errors.any()
    },
    itemIdToEdit () {
      return this.$store.state.repetitiveTaskManagement.repetitivesTask.id || 0
    },
  },
  methods: {
    fetch_data (id) {
      this.$store.dispatch('rangeManagement/fetchItem', id)
        .then(res => { 
            this.range_data = res.data.success
        })
        .catch(err => {
          if (err.response.status === 404) {
            this.range_not_found = true
            return
          }
          console.error(err) 
        })
    },
    save_changes () {
      /* eslint-disable */
      if (!this.validateForm) return
      this.$vs.loading()
       
      const payload = {...this.range_data}  
      payload.repetitive_tasks = this.repetitiveTasksData    
      this.$store.dispatch('rangeManagement/updateItem', payload)
        .then(() => { 
          this.$vs.loading.close() 
          this.$vs.notify({
          title: 'Modification',
          text: 'Gamme modifiée avec succès',
          iconPack: 'feather',
          icon: 'icon-alert-circle',
          color: 'success'
        })
        this.$router.push(`/${modelPlurial}`).catch(() => {})
        })
        .catch(error => {   
          const unauthorize = error.message ? error.message.includes('status code 403') : false
          const unauthorizeMessage = `Vous n'avez pas les autorisations pour cette action`
          this.$vs.loading.close()
          this.$vs.notify({
            title: 'Echec',
            text: unauthorize ? unauthorizeMessage : error.message,
            iconPack: 'feather',
            icon: 'icon-alert-circle',
            color: 'danger'
          })
        })
    },
    back () {
      this.$router.push(`/${modelPlurial}`).catch(() => {})
    },
    capitalizeFirstLetter (word) {
      if (typeof word !== 'string') return ''
        return word.charAt(0).toUpperCase() + word.slice(1)
    }
  },
  created () {
     // Register Module rangeManagement Module
    if (!moduleRangeManagement.isRegistered) {
      this.$store.registerModule('rangeManagement', moduleRangeManagement)
      moduleRangeManagement.isRegistered = true
    }
    if (!moduleSkillManagement.isRegistered) {
      this.$store.registerModule('skillManagement', moduleSkillManagement)
      moduleCompanyManagement.isRegistered = true
    }
    if (!moduleWorkareaManagement.isRegistered) {
      this.$store.registerModule('workareaManagement', moduleWorkareaManagement)
      moduleWorkareaManagement.isRegistered = true
    }
    if (!moduleRepetitiveTaskManagement.isRegistered) {
      this.$store.registerModule('repetitiveTaskManagement', moduleRepetitiveTaskManagement)
      moduleRepetitiveTaskManagement.isRegistered = true
    }
    this.fetch_data(this.$route.params.id)
    this.$store.dispatch('skillManagement/fetchItems').catch(err => { console.error(err) })
    this.$store.dispatch('workareaManagement/fetchItems').catch(err => { console.error(err) })
    this.$store.dispatch('repetitiveTaskManagement/cleanItems').catch(err => { console.error(err) })
    this.$store.dispatch('repetitiveTaskManagement/fetchItemsByRange', this.$route.params.id).catch(err => { console.error(err) })
  },
  beforeDestroy () {
    moduleRangeManagement.isRegistered = false
    moduleSkillManagement.isRegistered = false
    moduleWorkareaManagement.isRegistered = false
    moduleRepetitiveTaskManagement.isRegistered = false
    this.$store.unregisterModule('rangeManagement')
    this.$store.unregisterModule('skillManagement')
    this.$store.unregisterModule('workareaManagement')
    this.$store.unregisterModule('repetitiveTaskManagement')
  }
}

</script>
