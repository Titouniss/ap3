<template>
  <div :style="{'direction': $vs.rtl ? 'rtl' : 'ltr'}" v-if="!disabled">
    <!-- <feather-icon
      icon="Edit3Icon"
      svgClasses="h-5 w-5 mr-4 hover:text-primary cursor-pointer"
      v-if="authorizedToEdit"
      @click="editRecord"
    />-->
    <feather-icon
      icon="Trash2Icon"
      svgClasses="h-5 w-5 hover:text-danger cursor-pointer"
      v-if="authorizedToDelete"
      @click="confirmDeleteRecord"
    />
  </div>
</template>

<script>
var model = "hours";
var modelPlurial = "hours";
var modelTitle = "Heures";
export default {
  name: "CellRendererActions",
  computed: {
    disabled() {
      return false;
    },
    authorizedToEdit() {
      return this.$store.getters.userHasPermissionTo(`edit ${modelPlurial}`);
    },
    authorizedToDelete() {
      return this.$store.getters.userHasPermissionTo(`delete ${modelPlurial}`);
    },
  },
  methods: {
    editRecord() {
      this.$router
        .push(`/${modelPlurial}/${model}-edit/${this.params.data.id}`)
        .catch(() => {});
    },
    confirmDeleteRecord() {
      this.$vs.dialog({
        type: "confirm",
        color: "danger",
        title: "Confirmer suppression",
        text:
          this.params.data.duration == "01:00:00"
            ? `Voulez vous vraiment supprimer l'heure du ${
                this.params.data.start_at.split(" ")[0]
              } pour le projet ${this.params.data.project} ?`
            : `Voulez vous vraiment supprimer les ${
                this.params.data.duration.split(":")[0]
              } heures du ${
                this.params.data.start_at.split(" ")[0]
              } pour le projet ${this.params.data.project.name} ?`,
        accept: this.deleteRecord,
        acceptText: "Supprimer",
        cancelText: "Annuler",
      });
    },
    deleteRecord() {
      this.$store
        .dispatch("hoursManagement/removeRecord", this.params.data.id)
        .then(() => {
          this.showDeleteSuccess();
        })
        .catch((err) => {
          console.error(err);
        });
    },
    showDeleteSuccess() {
      this.$vs.notify({
        color: "success",
        title: modelTitle,
        text: `${modelTitle} supprimées`,
      });
    },
  },
};
</script>
