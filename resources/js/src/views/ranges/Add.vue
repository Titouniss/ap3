<!-- =========================================================================================
  File Name: RoleAdd.vue
  Description: role Add Page
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/role/pixinvent
========================================================================================== -->

<template>
  <div id="page-role-add">
    <vx-card>
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
                <span class="font-medium text-lg leading-none">Tâches</span>
            </div>
            <vs-divider />
            </div>
        </div>
         

      </div>
      <!-- Save & Reset Button -->
      <div class="vx-row">
        <div class="vx-col w-full">
          <div class="mt-8 flex flex-wrap items-center justify-end">
            <vs-button class="ml-auto mt-2" @click="save_changes" :disabled="!validateForm">Créer</vs-button>
            <vs-button class="ml-4 mt-2" type="border" color="warning" @click="back">Annuler</vs-button>
          </div>
        </div>
      </div>
    </vx-card>
  </div>
</template>

<script>
import lodash from 'lodash'
// Store Module
import moduleRangeManagement from '@/store/range-management/moduleRangeManagement.js'
var model = 'range'
var modelPlurial = 'ranges'
var modelTitle = 'Gammes'

export default {
  data () {
    return {
      range_data: {
        name: '',
        description: ''
      },
      selected: [],
    }
  },
  computed: {
    repetitive_tasks () {
        // const permissionsStore = this.$store.state.permissionManagement.permissions        
        let repetitiveTasks =  []
        // if (permissionsStore && permissionsStore.length > 0) {
        //         permissions = permissionsStore.reduce(function(acc, valeurCourante){
        //           let permissionName = valeurCourante.name
        //           let titles = permissionName.split(" ")
                  
        //           if (!acc) {
        //               acc = {}
        //           }
        //           if (titles.length > 1) {
        //               if (!acc[valeurCourante.name_fr]) {
        //                   acc[valeurCourante.name_fr] = {}
        //               }
        //               acc[valeurCourante.name_fr][titles[0]] = valeurCourante
        //           }             
        //       return acc;
        //       }, {});
        // }

      return repetitiveTasks
    },
    validateForm () {
      return !this.errors.any()
    }
  },
  methods: {
    save_changes () {
      /* eslint-disable */
      if (!this.validateForm) return
      this.$vs.loading()
      this.range_data.permissions = _.keys(_.pickBy(this.selected ))
       
      const payload = {...this.range_data}      
      this.$store.dispatch('rangeManagement/addItem', payload)
      .then(() => { 
        this.$vs.loading.close() 
        this.$vs.notify({
          title: 'Ajout',
          text: 'Gamme ajoutée avec succès',
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
    // this.$store.dispatch('permissionManagement/fetchItems').catch(err => { console.error(err) }) // TODO get repetitive tasks for company
  },
  beforeDestroy () {
    moduleRangeManagement.isRegistered = false
    this.$store.unregisterModule('rangeManagement')
  }
}

</script>
