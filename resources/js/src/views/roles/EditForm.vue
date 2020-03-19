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
        title="Edition"
        accept-text= "Modifier"
        cancel-text = "Annuler"
        button-cancel = "border"
        @cancel="init"
        @accept="submitTodo"
        @close="init"
        :is-valid="validateForm"
        :active.sync="activePrompt">
        <div>
            <form>


                <div class="vx-row">
                    <div class="vx-col w-full">
                        <vs-input v-validate="'required'" name="name" class="w-full mb-4 mt-5" placeholder="Titre" v-model="itemLocal.name" />
                        <vs-textarea rows="5" label="Ajouter description" v-model="itemLocal.description" />
                        <vs-select label="Permissions" v-model="itemLocal.permissions" class="w-full mt-5" multiple>
                              <vs-select-item :key="index" :value="item.id" :text="item.name" v-for="(item,index) in permissions" />
                        </vs-select>
                    </div>
                </div>

            </form>
        </div>
    </vs-prompt>
</template>

<script>

// Store Module
import modulePermissionManagement from '@/store/permission-management/modulePermissionManagement.js'

export default {
  props: {
    itemId: {
      type: Number,
      required: true
    }
  },
  data () {
    return {
      itemLocal: Object.assign({}, this.$store.getters['roleManagement/getItem'](this.itemId))
    }
  },
  computed: {
    activePrompt: {
      get () {
        return this.itemId && this.itemId > 0 ? true : false
      },
      set (value) {
        this.$store.dispatch("roleManagement/editItem", {})
          .then(()   => {  })
          .catch(err => { console.error(err)       })
      }
    },
    permissions () {
      return this.$store.state.permissionManagement.permissions
    },
    validateForm () {
      return !this.errors.any() && this.itemLocal.name !== ''
    }
  },
  methods: {
    init () {
      this.itemLocal = Object.assign({}, this.$store.getters['roleManagement/getItem'](this.itemId))
    },
    submitTodo () {
      this.$store.dispatch('roleManagement/updateItem', this.itemLocal)
    }
  },
  created () {
    if (!modulePermissionManagement.isRegistered) {
      this.$store.registerModule('permissionManagement', modulePermissionManagement)
      modulePermissionManagement.isRegistered = true
      console.log(Object.assign({}, this.$store.getters['roleManagement/getItem'](this.itemId)));
    }
    this.$store.dispatch('permissionManagement/fetchItems').catch(err => { console.error(err) })
  },
  beforeDestroy () {
    modulePermissionManagement.isRegistered = false
    this.$store.unregisterModule('permissionManagement')
  }
}
</script>
