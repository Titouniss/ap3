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
        <div class="vx-row" v-if="!disabled">
          <vs-switch v-model="role_data.isPublic" name="isPublic">
            <span slot="on">Publique</span>
            <span slot="off">Privé</span>
          </vs-switch>
          <span class="text-danger text-sm"  v-show="errors.has('isPublic')">{{ errors.first('isPublic') }}</span>
        </div>
        <div class="vx-row">
          <vs-input class="w-full mt-4" label="Titre" v-model="role_data.name" v-validate="'required'" name="name" />
          <span class="text-danger text-sm"  v-show="errors.has('name')">{{ errors.first('name') }}</span>
        </div>
        <div class="vx-row">
          <vs-textarea class="w-full mt-4" rows="5" label="Ajouter description" v-model="role_data.description" name="description"/>
          <span class="text-danger text-sm"  v-show="errors.has('description')">{{ errors.first('description') }}</span>
        </div>
        <!-- Permissions -->
        <div class="vx-row mt-4">
            <div class="vx-col w-full">
            <div class="flex items-end px-3">
                <feather-icon svgClasses="w-6 h-6" icon="LockIcon" class="mr-2" />
                <span class="font-medium text-lg leading-none">Permissions</span>
            </div>
            <vs-divider />
            </div>
        </div>

        <div class="block overflow-x-auto">
            <table class="w-full">
            <tr>
                <!--
                You can also use `Object.keys(Object.values(data_local.permissions)[0])` this logic if you consider,
                our data structure. You just have to loop over above variable to get table headers.
                Below we made it simple. So, everyone can understand.
                -->
                <th class="font-semibold text-base text-left px-3 py-2" v-for="heading in ['Module', 'Editer', 'Créer', 'Supprimer']" :key="heading">{{ heading }}</th>
            </tr>
            <tr v-for="(items,index) in permissions" :key="index">
                <td class="px-3 py-2">{{ index }}</td>
                <td v-for="(item,name) in items" class="px-3 py-2" :key="index+name+item.id">
                    <vs-checkbox v-model="selected[item.id]" />
                </td>
            </tr>
            </table>
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
import moduleRoleManagement from '@/store/role-management/moduleRoleManagement.js'
import modulePermissionManagement from '@/store/permission-management/modulePermissionManagement.js'
var model = 'role'
var modelPlurial = 'roles'
var modelTitle = 'Rôle'

export default {
  data () {
    return {
      role_data: {
        name: '',
        guard_name: 'web',
        description: '', 
        isPublic: false,
        company_id: null
      },
      selected: [],
      role_not_found: false,
    }
  },
  computed: {
    disabled () { 
      const user = this.$store.state.AppActiveUser 
      if (user.roles && user.roles.length > 0) {
        if (user.roles.find(r => r.name === 'superAdmin' || r.name === 'littleAdmin')) {
          return false
        } else  {
          this.role_data.company_id = user.company_id
          return true
        }
      } else return true
    },
    permissions () {
        const permissionsStore = this.$store.state.permissionManagement.permissions        
        let permissions = permissionsStore.reduce(function(acc, valeurCourante){
            let permissionName = valeurCourante.name
            let titles = permissionName.split(" ")            
            if (!acc) {
                acc = {}
            }
            if (titles.length > 1) {
                if (!acc[titles[1]]) {
                    acc[titles[1]] = {}
                }
                acc[titles[1]][titles[0]] = valeurCourante
            }             
        return acc;
        }, {});
      return permissions
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
      this.role_data.permissions = _.keys(_.pickBy(this.selected ))
       
      const payload = {...this.role_data}      
      this.$store.dispatch('roleManagement/addItem', payload)
      .then(() => { 
        this.$vs.loading.close() 
        this.$vs.notify({
          title: 'Ajout',
          text: 'Rôle ajouter avec succès',
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
    }
  },
  created () {
    // Register Module roleManagement Module
    if (!moduleRoleManagement.isRegistered) {
      this.$store.registerModule('roleManagement', moduleRoleManagement)
      moduleRoleManagement.isRegistered = true
    }
    if (!modulePermissionManagement.isRegistered) {
      this.$store.registerModule('permissionManagement', modulePermissionManagement)
      modulePermissionManagement.isRegistered = true
    }
    this.$store.dispatch('permissionManagement/fetchItems').catch(err => { console.error(err) })
  },
  beforeDestroy () {
    moduleRoleManagement.isRegistered = false
    modulePermissionManagement.isRegistered = false
    this.$store.unregisterModule('roleManagement')
    this.$store.unregisterModule('permissionManagement')
  }
}

</script>
