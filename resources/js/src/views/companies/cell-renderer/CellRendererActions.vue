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
var model = "company";
var modelPlurial = "companies";
var modelTitle = "Société";

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
        .dispatch("companyManagement/editItem", this.params.data)
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
          type === "delete"
            ? "Confirmer suppression"
            : type === "archive"
            ? "Confirmer archivage"
            : "Confirmer restauration",
        text:
          type === "delete"
            ? `Voulez vous vraiment supprimer la société ` +
              this.params.data.name +
              ` ?`
            : type === "archive"
            ? `Voulez vous vraiment archiver la société ` +
              this.params.data.name +
              ` ?`
            : `Voulez vous vraiment restaurer la société ` +
              this.params.data.name +
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
        .dispatch("companyManagement/forceRemoveItem", this.params.data.id)
        .then(response => {
          console.log(response);
          this.showActionSuccess("delete");
        })
        .catch(err => {
          console.error(err);
        });
    },
    archiveRecord() {
      this.$store
        .dispatch("companyManagement/removeItem", this.params.data.id)
        .then(data => {
          this.showActionSuccess("archive");
        })
        .catch(err => {
          console.error(err);
        });
    },
    restoreRecord() {
      console.log([" this.params.data.id", this.params.data.id]);
      this.$store
        .dispatch("companyManagement/restoreItem", this.params.data.id)
        .then(response => {
          console.log(["response", response]);
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
            ? `Société supprimée`
            : type === "archive"
            ? `Société archivée`
            : `Société restaurée`
      });
    },
    showActionError() {
      this.$vs.notify({
        color: "error",
        title: modelTitle,
        text: "Une erreur est survenue."
      });
    }
  }
};
</script>
