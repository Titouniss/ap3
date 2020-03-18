<template>
  <div id="page-role-edit">
    <vs-alert color="danger" title="role Not Found" :active.sync="role_not_found">
      <span>Le rôle {{ $route.params.id }} est introuvable. </span>
      <span>
        <span>voir </span><router-link :to="{name:'roles'}" class="text-inherit underline">Tout les rôles</router-link>
      </span>
    </vs-alert>

    <vx-card v-if="role_data">
      <div slot="no-body" class="tabs-container px-6 pt-6">
        <div class="vx-row">
          <vs-input class="w-full mt-4" label="Titre" v-model="role_data.name" v-validate="'required|alpha_spaces'" name="name" />
          <span class="text-danger text-sm"  v-show="errors.has('name')">{{ errors.first('name') }}</span>
        </div>
        <div class="vx-row">
          <vs-input class="w-full mt-4" label="Description" v-model="role_data.description" name="description" />
          <span class="text-danger text-sm"  v-show="errors.has('description')">{{ errors.first('description') }}</span>
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
var model = 'role'
var modelPlurial = 'roles'
var modelTitle = 'Rôle'
// Store Module
import moduleManagement from '@/store/role-management/moduleRoleManagement.js'

export default {
  data () {
    return {
      role_data: null,
      role_not_found: false
    }
  },
  computed: {
    validateForm () {
      return !this.errors.any()
    }
  },
  methods: {
    fetch_data (id) {
      this.$store.dispatch('roleManagement/fetchRole', id)
        .then(res => { this.role_data = res.data.success })
        .catch(err => {
          if (err.response.status === 404) {
            this.role_not_found = true
            return
          }
          console.error(err) 
        })
    },
    save_changes () {
      /* eslint-disable */
      if (!this.validateForm) return
      this.$vs.loading()
      const payload = {...this.role_data}      
      this.$store.dispatch('roleManagement/updateRole', payload)
      .then(() => { this.$vs.loading.close() })
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
    back () {
      this.$router.push(`/${modelPlurial}`).catch(() => {})
    }
  },
  created () {
    // Register Module roleManagement Module
    if (!moduleManagement.isRegistered) {
      this.$store.registerModule('roleManagement', moduleManagement)
      moduleManagement.isRegistered = true
    }
    this.fetch_data(this.$route.params.id)
  },
}

</script>
