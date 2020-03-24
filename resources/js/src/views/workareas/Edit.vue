<!-- =========================================================================================
  File Name: RoleEdit.vue
  Description: role Edit Page
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/role/pixinvent
========================================================================================== -->

<template>
  <div id="page-role-edit">
    <vs-alert color="danger" title="role Not Found" :active.sync="workarea_not_found">
      <span>L'îlot {{ $route.params.id }} est introuvable. </span>
      <span>
        <span>voir </span><router-link :to="{name:'workarea'}" class="text-inherit underline">Tout les îlots</router-link>
      </span>
    </vs-alert>

    <vx-card v-if="itemLocal">
      <div slot="no-body" class="tabs-container px-6 pt-6">
        <div class="vx-row">
          <vs-input class="w-full mt-4" label="Titre" v-model="itemLocal.name" v-validate="'required'" name="name" />
          <span class="text-danger text-sm"  v-show="errors.has('name')">{{ errors.first('name') }}</span>
        </div>
        <div class="vx-row" v-if="companiesData">
          <vs-select v-on:change="selectCompanySkills" v-validate="'required'" label="Compagnie" v-model="itemLocal.company_id" :color="validateForm ? 'success' : 'danger'" class="w-full mt-5">
            <vs-select-item :key="index" :value="item.id" :text="item.name" v-for="(item,index) in companiesData" />
          </vs-select>
          <span class="text-danger text-sm"  v-show="errors.has('company_id')">{{ errors.first('company_id') }}</span>
        </div>
        <div class="vx-row" v-if="itemLocal.company_id && companySkills.length > 0">
          <vs-select v-validate="'required'" label="Compétences" v-model="itemLocal.skills" class="w-full mt-5" multiple autocomplete>
            <vs-select-item :key="index" :value="item.id" :text="item.name" v-for="(item,index) in companySkills" />
          </vs-select>
          <span class="text-danger text-sm"  v-show="errors.has('company_id')">{{ errors.first('company_id') }}</span>
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
  </div>
</template>

<script>
var model = 'workarea'
var modelPlurial = 'workareas'
var modelTitle = 'Ilot'

import lodash from 'lodash'
// Store Module
import moduleWorkareaManagement from '@/store/workarea-management/moduleWorkareaManagement.js'
import moduleCompanyManagement from '@/store/company-management/moduleCompanyManagement.js'

export default {
  data () {
    return {
      itemLocal: null,
      workarea_not_found: false,
      companySkills: []
    }
  },
  computed: {
    companiesData () {
      return this.$store.state.companyManagement.companies
    },
    validateForm () {
      return !this.errors.any()
    }
  },
  methods: {
    fetch_data (id) {
      this.$store.dispatch('workareaManagement/fetchItem', id)
        .then(res => { 
            let item = res.data.success
            let skill_ids = []
            if(item.skills.length > 0){
              item.skills.forEach(element => {
                skill_ids.push(element.skill_id)
              });
            }
            item.skills = skill_ids
            this.itemLocal = item
        })
        .catch(err => {
          if (err.response.status === 404) {
            this.workarea_not_found = true
            return
          }
          console.error(err) 
        })
    },
    save_changes () {
      /* eslint-disable */
      if (!this.validateForm) return
      this.$vs.loading()
      const payload = {...this.itemLocal}   
      this.$store.dispatch('workareaManagement/updateItem', payload)
      .then(() => { 
        this.$vs.loading.close() 
        this.$vs.notify({
          title: 'Modification d\'un îlot',
          text: `"${this.itemLocal.name}" modifié avec succès`,
          iconPack: 'feather',
          icon: 'icon-alert-circle',
          color: 'success'
        })
        this.$router.push(`/${modelPlurial}`).catch(() => {}) })
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
    },
    selectCompanySkills (item) {
      this.itemLocal.skills = []
      this.companySkills = this.companiesData.find(company => company.id === item).skills
    },
    back () {
      this.$router.push(`/${modelPlurial}`).catch(() => {})
    }
  },
  watch: {
    companiesData(){
      this.companySkills = this.companiesData.length > 0 ? this.companiesData.find(company => company.id === this.itemLocal.company_id).skills : []
    }
  },
  created () {
    // Register Module workareaManagement Module
    if (!moduleWorkareaManagement.isRegistered) {
      this.$store.registerModule('workareaManagement', moduleWorkareaManagement)
      moduleWorkareaManagement.isRegistered = true
    }
    if (!moduleCompanyManagement.isRegistered) {
      this.$store.registerModule('companyManagement', moduleCompanyManagement)
      moduleCompanyManagement.isRegistered = true
    }
    this.$store.dispatch('companyManagement/fetchItems').catch(err => { console.error(err) })
    this.fetch_data(this.$route.params.id)
  },
  beforeDestroy () {
    moduleWorkareaManagement.isRegistered = false
    moduleCompanyManagement.isRegistered = false
    this.$store.unregisterModule('workareaManagement')
    this.$store.unregisterModule('companyManagement')
  }
}

</script>
