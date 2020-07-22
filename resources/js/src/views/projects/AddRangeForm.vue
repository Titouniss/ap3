<template>
  <div>
    
    <vs-button @click="activePrompt=true" size="small" color="success" type="gradient" icon-pack="feather" icon="icon-plus"></vs-button>
    <vs-prompt
      title="Ajouter une gamme au projet"
      accept-text= "Ajouter"
      cancel-text= "Annuler"
      button-cancel = "border"
      @cancel="clearFields"
      @accept="addRange"
      @close="clearFields"
      :is-valid="validateForm"
      :active.sync="activePrompt">
      <div>
        <form>
          <vx-tooltip
          style="z-index: 52007;"
          title="Information"
          color="dark"
          text="Le préfixe va vous servir afin de différencier vos différentes gammes">
            <vs-input icon-pack="feather" icon="icon-info" v-validate="'required|numeric'" name="prefix" class="w-full mb-4 mt-1" 
              placeholder="Préfixe" v-model="itemLocal.prefix" maxlength="5" @input="onPrefixChange"/>
            <vs-select v-model="itemLocal.rangeId" class="w-full" v-validate="'required'" name='range'>
              <vs-select-item :key="index" :value="item.id" :text="item.name" v-for="(item,index) in rangesData" />
            </vs-select>
          </vx-tooltip>
        </form>
      </div>
    </vs-prompt>
  </div>
</template>

<script>
import moment from 'moment'
import { Validator } from 'vee-validate';
import errorMessage from './errorValidForm';

// register custom messages
Validator.localize('fr', errorMessage);

export default {
  props: {
    company_id: {
      required: true
    },
    project_id: {
      required: true
    },
  },
  data () {
    return {
      activePrompt: false,

      itemLocal: {
        prefix: '',
        rangeId: ''
      }
    }
  },
  computed: {
    validateForm () {
      return this.itemLocal.prefix != '' && this.itemLocal.rangeId != null && this.itemLocal.rangeId != ''
    },
    rangesData() {
      return this.filterItemsAdmin(this.$store.state.rangeManagement.ranges)
    }
  },
  methods: {
    clearFields () {
      Object.assign(this.itemLocal, {
        prefix: '',
        rangeId: ''
      })
    },
    addRange () {
      let range = this.rangesData.filter(e => e.id == this.itemLocal.rangeId)
      this.itemLocal.project_id = this.project_id

      this.$store.dispatch('taskManagement/addItemRange', Object.assign({}, this.itemLocal))
      .then(() => { 
          this.$vs.loading.close() 
          this.$vs.notify({
          title: 'Ajout d\'une gamme au projet',
          text: `"${range[0].name}" ajouté avec succès`,
          iconPack: 'feather',
          icon: 'icon-alert-circle',
          color: 'success'
          })
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
    },
    filterItemsAdmin (items) {
      let filteredItems = []
      const user = this.$store.state.AppActiveUser 
      if (user.roles && user.roles.length > 0) {
        if (user.roles.find(r => r.name === 'superAdmin' || r.name === 'littleAdmin')) {
          filteredItems = items.filter((item) => item.company.id === this.company_id)
        }
        else{
          filteredItems = items
        }
      }
      return filteredItems
    },
    onPrefixChange(){
      this.itemLocal.prefix = this.itemLocal.prefix.toUpperCase()
    }
  }
}
</script>
<style> .vs-tooltip { z-index: 99999 !important; } </style>
