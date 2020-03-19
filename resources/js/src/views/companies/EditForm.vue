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
                    <div class="vx-col ml-auto flex">
                        <vs-dropdown class="cursor-pointer flex" vs-custom-content>
                            <feather-icon icon="TagIcon" svgClasses="h-5 w-5" />
                            <vs-dropdown-menu style="z-index: 200001">
                                  <vs-dropdown-item @click.stop v-for="(perm, index) in permissions" :key="index">
                                      <vs-checkbox :vs-value="perm.value" v-model="itemLocal.tags">{{ perm.name }}</vs-checkbox>
                                  </vs-dropdown-item>
                            </vs-dropdown-menu>
                        </vs-dropdown>
                    </div>
                </div>

                <div class="vx-row">
                    <div class="vx-col w-full">
                        <vs-input v-validate="'required'" name="name" class="w-full mb-4 mt-5" placeholder="Titre" v-model="itemLocal.name" />
                        <vs-textarea rows="5" label="Ajouter description" v-model="itemLocal.description" />
                    </div>
                </div>

            </form>
        </div>
    </vs-prompt>
</template>

<script>
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
      return this.$store.state.roleManagement.permissions
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
  }
}
</script>
