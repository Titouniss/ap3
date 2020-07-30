<template>
  <div :style="{'direction': $vs.rtl ? 'rtl' : 'ltr'}" v-if="!disabled">
    <feather-icon
      icon="Edit3Icon"
      svgClasses="h-5 w-5 mr-4 hover:text-primary cursor-pointer"
      v-if="authorizedToEdit && !params.data.deleted_at"
      @click="editRecord"
    />
    <feather-icon
      v-if="authorizedToEdit"
      icon="ArchiveIcon"
      :svgClasses="this.archiveSvg"
      @click="params.data.deleted_at ? confirmActionRecord('restore') : confirmActionRecord('archive')"
    />
    <feather-icon
      v-if="authorizedToDelete"
      icon="Trash2Icon"
      svgClasses="h-5 w-5 hover:text-danger cursor-pointer"
      @click="confirmActionRecord('delete')"
    />
  </div>
</template>

<script>
var model = "user";
var modelPlurial = "users";
var modelTitle = "Utilisateur";
export default {
  name: "CellRendererActions",
  computed: {
    archiveSvg() {
      return this.params.data.deleted_at
        ? "h-5 w-5 mr-4 text-success cursor-pointer"
        : "h-5 w-5 mr-4 hover:text-warning cursor-pointer";
    },
    authorizedToDelete() {
      return this.$store.getters.userHasPermissionTo(`delete ${modelPlurial}`);
    },
    authorizedToEdit() {
      return this.$store.getters.userHasPermissionTo(`edit ${modelPlurial}`);
    },
    disabled() {
      return (
        this.params.data.roles &&
        this.params.data.roles.find(r => r.name === "superAdmin")
      );
    }
  },
  methods: {
    editRecord() {
      this.$router
        .push(`/${modelPlurial}/${model}-edit/${this.params.data.id}`)
        .catch(() => {});
    },
    confirmActionRecord(type) {
      const name = `${this.params.data.firstname} ${this.params.data.lastname}`;
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
            ? `Voulez vous vraiment supprimer l'utilisateur ${name}`
            : type === "archive"
            ? `Voulez vous vraiment archiver l'utilisateur ${name}`
            : `Voulez vous vraiment restaurer l'utilisateur ${name}`,
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
    archiveRecord() {
      this.$store
        .dispatch("userManagement/removeItem", this.params.data.id)
        .then(response => {
          if (response.data.success) {
            this.showActionSuccess("archive");
          } else {
            this.showActionError();
          }
        });
    },
    restoreRecord() {
      this.$store
        .dispatch("userManagement/restoreItem", this.params.data.id)
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
    deleteRecord() {
      this.$store
        .dispatch("userManagement/forceRemoveItem", this.params.data.id)
        .then(response => {
          if (response.data.success) {
            this.showActionSuccess("delete");
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
            ? `Utilisateur supprimé`
            : type === "archive"
            ? `Utilisateur archivé`
            : `Utilisateur restauré`
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
