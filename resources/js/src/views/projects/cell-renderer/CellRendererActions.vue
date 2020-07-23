<template>
  <div :style="{'direction': $vs.rtl ? 'rtl' : 'ltr'}">
    <feather-icon
      icon="Edit3Icon"
      svgClasses="h-5 w-5 mr-4 hover:text-primary cursor-pointer"
      @click="$router.push(url)"
    />
    <feather-icon
      icon="ArchiveIcon"
      svgClasses="h-5 w-5 mr-4 hover:text-primary cursor-pointer"
      @click="confirmDeleteRecord('archive')"
    />
    <feather-icon
      icon="Trash2Icon"
      svgClasses="h-5 w-5 hover:text-danger cursor-pointer"
      @click="confirmDeleteRecord('delete')"
    />
  </div>
</template>

<script>
var model = "project";
var modelPlurial = "projects";
var modelTitle = "Projet";
export default {
  name: "CellRendererActions",
  computed: {
    url() {
      return `/${modelPlurial}/${model}-view/${this.params.data.id}`;
    }
  },
  methods: {
    editRecord() {
      this.$store
        .dispatch("projectManagement/editItem", this.params.data)
        .then(() => {})
        .catch(err => {
          console.error(err);
        });
    },
    confirmDeleteRecord(type) {
      this.$vs.dialog({
        type: "confirm",
        color: "danger",
        title:
          type === "delete" ? "Confirmer suppression" : "Confirmer archivation",
        text:
          type === "delete"
            ? `Voulez vous vraiment supprimer le projet ` +
              this.params.data.name +
              ` ?`
            : `Voulez vous vraiment archiver le projet ` +
              this.params.data.name +
              ` ?`,
        accept: type === "delete" ? this.deleteRecord : this.archiveRecord,
        acceptText: type === "delete" ? "Supprimer" : "Archiver",
        cancelText: "Annuler"
      });
    },
    deleteRecord() {
      this.$store
        .dispatch("projectManagement/forceRemoveItem", this.params.data.id)
        .then(() => {
          this.showDeleteSuccess("delete");
        })
        .catch(err => {
          console.error(err);
        });
    },
    archiveRecord() {
      this.$store
        .dispatch("projectManagement/removeItem", this.params.data.id)
        .then(data => {
          this.showDeleteSuccess("archive");
        })
        .catch(err => {
          console.error(err);
        });
    },
    showDeleteSuccess(type) {
      this.$vs.notify({
        color: "success",
        title: modelTitle,
        text: type === "delete" ? `Projet supprimé` : `Projet archivé`
      });
    }
  }
};
</script>
