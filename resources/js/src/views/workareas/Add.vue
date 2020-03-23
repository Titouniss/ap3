<!-- =========================================================================================
  File Name: Workarea Add.vue
  Description: Workarea Add Page
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/role/pixinvent
========================================================================================== -->

<template>
  <div id="page-workarea-add">
    <vx-card>
      <div slot="no-body" class="tabs-container px-6 pt-6">
        <div class="vx-row">
          <vs-input class="w-full mt-4" label="Titre" v-model="itemLocal.name" v-validate="'required'" name="name" />
          <span class="text-danger text-sm"  v-show="errors.has('name')">{{ errors.first('name') }}</span>
        </div>
        <div class="vx-row">
          <vs-select v-validate="'required'" label="Compagnie" v-model="itemLocal.company_id" class="w-full mt-5">
            <vs-select-item :key="index" :value="item.id" :text="item.name" v-for="(item,index) in companiesData" />
          </vs-select>
          <span class="text-danger text-sm"  v-show="errors.has('company_id')">{{ errors.first('company_id') }}</span>
        </div>
        <!-- Permissions -->
        <div class="vx-row mt-4">
            <div class="vx-col w-full">
            <div class="flex items-end px-3">
                <feather-icon svgClasses="w-6 h-6" icon="BookOpenIcon" class="mr-2" />
                <span class="font-medium text-lg leading-none">Compétences</span>
            </div>
            <vs-divider />
            </div>
        </div>
         

      </div>
      <!-- Save & Reset Button -->
      <div class="vx-row">
        <div class="vx-col w-full">
          <div class="mt-8 flex flex-wrap items-center justify-end">
            <vs-button class="ml-auto mt-2" @click="save_changes" :disabled="!validateForm">Ajouter</vs-button>
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
import moduleWorkareaManagement from '@/store/workarea-management/moduleWorkareaManagement.js'
import moduleCompanyManagement from '@/store/company-management/moduleCompanyManagement.js'
var model = 'workarea'
var modelPlurial = 'workareas'
var modelTitle = 'Ilot'

export default {
  data () {
    return {
      itemLocal: {
        name: '',
        company_id: null
      },
      selected: [],
    }
  },
  computed: {
    companiesData() {
      return this.$store.state.companyManagement.companies
    },
    validateForm () {
      return !this.errors.any()
    }
  },
  methods: {
    save_changes () {
      this.$validator.validateAll().then(result => {
        if (result) {
          this.$store.dispatch('workareaManagement/addItem', Object.assign({}, this.itemLocal))
          .then(() => { 
            this.$vs.loading.close() 
            this.$vs.notify({
              title: 'Ajout d\'un îlot',
              text: `"${this.itemLocal.name}" ajoutée avec succès`,
              iconPack: 'feather',
              icon: 'icon-alert-circle',
              color: 'success'
            })
            this.$router.push(`/${modelPlurial}`).catch(() => {})
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
    back () {
      this.$router.push(`/${modelPlurial}`).catch(() => {})
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
  },
  beforeDestroy () {
    moduleWorkareaManagement.isRegistered = false
    moduleCompanyManagement.isRegistered = false
    this.$store.unregisterModule('workareaManagement')
    this.$store.unregisterModule('companyManagement')
  }
}

</script>
