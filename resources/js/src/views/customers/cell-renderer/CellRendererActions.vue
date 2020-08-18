<template>
  <div :style="{'direction': $vs.rtl ? 'rtl' : 'ltr'}">
    <feather-icon
      icon="Edit3Icon"
      svgClasses="h-5 w-5 mr-4 hover:text-primary cursor-pointer"
      v-if="authorizedToEdit && !params.data.deleted_at"
      @click="editRecord"
    />
    <feather-icon
      icon="ArchiveIcon"
      :svgClasses="this.archiveSvg"
      v-if="authorizedToDelete"
      @click="params.data.deleted_at ? confirmActionRecord('restore') : confirmActionRecord('archive')"
    />
    <feather-icon
      icon="Trash2Icon"
      svgClasses="h-5 w-5 hover:text-danger cursor-pointer"
      v-if="authorizedToDelete"
      @click="confirmActionRecord('delete')"
    />
  </div>
</template>

<script>
var model = "customer";
var modelPlurial = "customers";
var modelTitle = "Client";
export default {
  name: "CellRendererActions",
  computed: {
    authorizedToEdit() {
      return this.$store.getters.userHasPermissionTo(`edit ${modelPlurial}`);
    },
    authorizedToDelete() {
      return this.$store.getters.userHasPermissionTo(`delete ${modelPlurial}`);
    },
    archiveSvg() {
      return this.params.data.deleted_at
        ? "h-5 w-5 mr-4 text-success cursor-pointer"
        : "h-5 w-5 mr-4 hover:text-warning cursor-pointer";
    }
  },
  methods: {
    editRecord() {
      this.$store
        .dispatch("customerManagement/editItem", this.params.data)
        .then(() => {})
        .catch(err => {
          console.error(err);
        });
    },
    confirmActionRecord(type) {
      this.$vs.dialog({
        type: "confirm",
        color:
          type === "delete"
            ? "danger"
            : type === "archive"
            ? "warning"
            : "success",
        title:
          type === "delete" ? "Confirmer suppression" : "Confirmer archivage",
        text:
          type === "delete"
            ? `Voulez vous vraiment supprimer le Client ` +
              this.params.data.lastname +
              ` ?`
            : type === "archive"
            ? `Voulez vous vraiment archiver le Client ` +
              this.params.data.lastname +
              ` ?`
            : `Voulez vous vraiment restaurer le Client ` +
              this.params.data.lastname +
              ` ?`,
        accept:
          type === "delete"
            ? this.deleteRecord
            : type === "archive"
            ? this.archiveRecord
            : this.restoreRecord,
        acceptText:
          type === "delete"
            ? "Supprimer"
            : type === "archive"
            ? "Archiver"
            : "Restaurer",
        cancelText: "Annuler"
      });
    },
    deleteRecord() {
      this.$store
        .dispatch("customerManagement/forceRemoveItem", this.params.data.id)
        .then(() => {
          this.showActionSuccess("delete");
        })
        .catch(err => {
          console.error(err);
        });
    },
    archiveRecord() {
      this.$store
        .dispatch("customerManagement/removeItem", this.params.data.id)
        .then(data => {
          this.showActionSuccess("archive");
        })
        .catch(err => {
          console.error(err);
        });
    },
    restoreRecord() {
      this.$store
        .dispatch("customerManagement/restoreItem", this.params.data.id)
        .then(response => {
          if (response.data.success) {
            this.showActionSuccess("restore");
          } else {
            this.showActionError();
          }
        })
        .catch(err => {
          console.error(err);
        });
    },
    showActionSuccess(type) {
      this.$vs.notify({
        color: "success",
        title: modelTitle,
        text:
          type === "delete"
            ? `Client supprimé`
            : type === "archive"
            ? `Client archivé`
            : `Client restauré`
      });
    }
  }
};
</script>
