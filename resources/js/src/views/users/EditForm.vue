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
                      <vs-input
                        v-validate="'required|alpha_dash|min:3'"
                        data-vv-validate-on="blur"
                        label-placeholder="Nom"
                        name="lastname"
                        placeholder="Nom"
                        v-model="itemLocal.lastname"
                        class="w-full mt-8" />
                      <span class="text-danger text-sm">{{ errors.first('lastname') }}</span>
                      
                      <vs-input
                        v-validate="'required|alpha_dash|min:3'"
                        data-vv-validate-on="blur"
                        label-placeholder="Prénom"
                        name="firstname"
                        placeholder="Prénom"
                        v-model="itemLocal.firstname"
                        class="w-full mt-8" />
                      <span class="text-danger text-sm">{{ errors.first('firstname') }}</span>

                      <vs-select label="Rôle" v-model="selected" class="w-full mt-5">
                            <vs-select-item :key="index" :value="item.id" :text="item.name" v-for="(item,index) in roles" />
                        </vs-select>
                    </div>
                </div>

            </form>
        </div>
    </vs-prompt>
</template>

<script>

// Store Module
import moduleRoleManagement from '@/store/role-management/moduleRoleManagement.js'

export default {
  props: {
    itemId: {
      type: Number,
      required: true
    }
  },
  data () {
    return {
      itemLocal: Object.assign({}, this.$store.getters['userManagement/getItem'](this.itemId)),
      selected: this.$store.getters['userManagement/getRole'](this.itemId)
    }
  },
  computed: {
    activePrompt: {
      get () {
        return this.itemId && this.itemId > 0 ? true : false
      },
      set (value) {
        this.$store.dispatch("userManagement/editItem", {})
          .then(()   => {  })
          .catch(err => { console.error(err)       })
      }
    },
    roles () {            
      return this.$store.getters['roleManagement/getItems']
    },
    validateForm () {
      return !this.errors.any() && this.firstname !== '' && this.lastname !== ''
    }
  },
  methods: {
    init () {
      this.itemLocal = Object.assign({}, this.$store.getters['userManagement/getItem'](this.itemId))
    },
    submitTodo () {
      this.itemLocal.roles = [this.$store.getters['roleManagement/getItem'](this.selected)]           
      this.$store.dispatch('userManagement/updateItem', this.itemLocal)
    }
  },
  created () {
    if (!moduleRoleManagement.isRegistered) {
      this.$store.registerModule('roleManagement', moduleRoleManagement)
      moduleRoleManagement.isRegistered = true
    }
    this.$store.dispatch('roleManagement/fetchItems').catch(err => { console.error(err) })
  }
}
</script>
