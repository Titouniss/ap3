<template>
    <div :style="{'direction': $vs.rtl ? 'rtl' : 'ltr'}">
      <feather-icon icon="Edit3Icon" svgClasses="h-5 w-5 mr-4 hover:text-primary cursor-pointer" @click="editRecord" />
      <feather-icon icon="Trash2Icon" svgClasses="h-5 w-5 hover:text-danger cursor-pointer" @click="confirmDeleteRecord" />
    </div>
</template>

<script>
var modelTitle = 'Etape'
export default {
  name: 'CellRendererActions',
  props: {
    item: {
      required: true
    }
  },
  methods: {
    editRecord () {
      this.$store.dispatch("repetitiveTaskManagement/editItem", this.item)
        .then(()   => {  })
        .catch(err => { console.error(err)       })
    },
    confirmDeleteRecord () {
      this.$vs.dialog({
        type: 'confirm',
        color: 'danger',
        title: 'Confirmer suppression',
        text: `Vous allez supprimer "${this.item.name}"`,
        accept: this.deleteRecord,
        acceptText: 'Supprimer',
        cancelText: 'Annuler',
      })
    },
    deleteRecord () {
      this.$store.dispatch("repetitiveTaskManagement/removeItem", this.item.id)
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
