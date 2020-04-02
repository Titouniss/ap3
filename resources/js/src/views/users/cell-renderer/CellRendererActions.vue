<template>
    <div :style="{'direction': $vs.rtl ? 'rtl' : 'ltr'}" v-if="!disabled">
      <feather-icon icon="Edit3Icon" svgClasses="h-5 w-5 mr-4 hover:text-primary cursor-pointer" v-if="authorizedToEdit" @click="editRecord"  />
      <feather-icon icon="Trash2Icon" svgClasses="h-5 w-5 hover:text-danger cursor-pointer" v-if="authorizedToDelete" @click="confirmDeleteRecord" />
    </div>
</template>

<script>
var model = 'user'
var modelPlurial = 'users'
var modelTitle = 'Utilisateur'
export default {
  name: 'CellRendererActions',
  computed:{
    authorizedToDelete () {               
      return this.$store.getters.userHasPermissionTo( `delete ${modelPlurial}`) > -1
    },
    authorizedToEdit () {
      return this.$store.getters.userHasPermissionTo( `edit ${modelPlurial}`) > -1
    },
    disabled () {                  
      return this.params.data.roles && this.params.data.roles.find(r => r.name === 'superAdmin')
    }
  },
  methods: {
    editRecord () {
      this.$store.dispatch("userManagement/editItem", this.params.data)
        .then(()   => {  })
        .catch(err => { console.error(err)       })
    },
    confirmDeleteRecord () {
      this.$vs.dialog({
        type: 'confirm',
        color: 'danger',
        title: 'Confirmer suppression',
        text: `Vous allez supprimer "${this.params.data.firstname} ${this.params.data.lastname}"`,
        accept: this.deleteRecord,
        acceptText: 'Supprimer !',
        cancelText: 'Annuler',
      })
    },
    deleteRecord () {
      this.$store.dispatch("userManagement/removeRecord", this.params.data.id)
        .then(()   => { this.showDeleteSuccess() })
        .catch(err => { console.error(err)       })
    },
    showDeleteSuccess () {
      this.$vs.notify({
        color: 'success',
        title: modelTitle,
        text: `${modelTitle} supprimé`
      })
    }
  }
}
</script>
