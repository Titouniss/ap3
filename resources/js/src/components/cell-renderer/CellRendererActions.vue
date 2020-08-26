<template>
  <div class="flex justify-end" :style="{ direction: $vs.rtl ? 'rtl' : 'ltr' }" v-if="!disabled">
    <vx-tooltip
      v-if="authorizedToEdit && !params.data.deleted_at && canEdit"
      text="Modifier"
      delay=".5s"
      class="mx-2"
    >
      <feather-icon
        icon="Edit3Icon"
        svgClasses="h-5 w-5 hover:text-primary cursor-pointer"
        @click="editRecord"
      />
    </vx-tooltip>
    <vx-tooltip
      v-if="authorizedToDelete && usesSoftDelete && canArchive"
      :text="params.data.deleted_at ? 'Restaurer' : 'Archiver'"
      delay=".5s"
      class="mx-2"
    >
      <feather-icon
        icon="ArchiveIcon"
        :svgClasses="this.archiveSvg"
        @click="params.data.deleted_at ? confirmActionRecord(dialogTypes.restore) : confirmActionRecord(dialogTypes.archive)"
      />
    </vx-tooltip>
    <vx-tooltip
      v-if="authorizedToDelete && (params.data.deleted_at || !usesSoftDelete) && canDelete"
      text="Supprimer"
      delay=".5s"
      class="mx-2"
    >
      <feather-icon
        icon="Trash2Icon"
        svgClasses="h-5 w-5 hover:text-danger cursor-pointer"
        @click="confirmActionRecord(dialogTypes.delete)"
      />
    </vx-tooltip>

    <vs-prompt
      type="confirm"
      :color="dialogColors[dialogType]"
      :title="dialogTitles[dialogType]"
      @cancel="dialogOpen=false"
      @accept="dialogType ? dialogActions[dialogType]() : null"
      acceptText="Confirmer"
      cancelText="Annuler"
      :active.sync="dialogOpen"
    >
      <div>
        <div>{{dialogMessage}}</div>
        <div v-if="dialogFootNote" class="mt-3">
          <small>{{dialogFootNote}}</small>
        </div>
      </div>
    </vs-prompt>
  </div>
</template>

<script>
export default {
  name: "CellRendererActions",
  data() {
    return {
      dialogOpen: false,
      dialogMessage: "",
      dialogFootNote: "",
      dialogType: "",
      dialogTypes: {
        restore: "restore",
        archive: "archive",
        delete: "delete"
      },
      dialogColors: {
        restore: "success",
        archive: "warning",
        delete: "danger"
      },
      dialogTitles: {
        restore: "Confirmer restauration",
        archive: "Confirmer archivage",
        delete: "Confirmer suppression"
      },
      dialogActions: {
        restore: this.restoreRecord,
        archive: this.archiveRecord,
        delete: this.deleteRecord
      }
    };
  },
  computed: {
    name() {
      return this.params.name
        ? this.params.name(this.params.data)
        : this.params.data.name;
    },
    model() {
      return this.params.model;
    },
    modelPlurial() {
      return this.params.modelPlurial;
    },
    withPrompt() {
      return this.params.withPrompt;
    },
    usesSoftDelete() {
      return this.params.usesSoftDelete !== undefined
        ? this.params.usesSoftDelete
        : true;
    },
    linkedTables() {
      let returnString = "";
      if (this.params.linkedTables) {
        let lastElement = "";
        if (this.params.linkedTables.length > 1) {
          lastElement = this.params.linkedTables[
            this.params.linkedTables.length - 1
          ];
          if (this.params.linkedTables.length > 3) {
            returnString =
              this.params.linkedTables
                .filter((table, index) => index <= 3)
                .map(table => `ses ${table}`)
                .join(", ") + "...";
          } else {
            returnString =
              this.params.linkedTables
                .filter(
                  (table, index) =>
                    index !== this.params.linkedTables.length - 1
                )
                .map(table => `ses ${table}`)
                .join(", ") + (lastElement ? ` et ses ${lastElement}` : "");
          }
        } else {
          returnString = `ses ${this.params.linkedTables[0]}`;
        }
      }
      return returnString;
    },
    canEdit() {
      return this.params.canEdit ? this.params.canEdit(this.params.data) : true;
    },
    canArchive() {
      return this.params.canArchive
        ? this.params.canArchive(this.params.data)
        : true;
    },
    canDelete() {
      return this.params.canDelete
        ? this.params.canDelete(this.params.data)
        : true;
    },
    blockDelete() {
      return this.params.blockDelete
        ? this.params.blockDelete(this.params.data)
        : false;
    },
    blockDeleteMessage() {
      return this.params.blockDeleteMessage
        ? this.params.blockDeleteMessage(this.params.data)
        : `Impossible à supprimer ${name}`;
    },
    disabled() {
      return this.params.disabled
        ? this.params.disabled(this.params.data)
        : false;
    },
    archiveSvg() {
      return this.params.data.deleted_at
        ? "h-5 w-5 text-success cursor-pointer"
        : "h-5 w-5 hover:text-warning cursor-pointer";
    },
    authorizedToDelete() {
      return this.$store.getters.userHasPermissionTo(
        `delete ${this.modelPlurial}`
      );
    },
    authorizedToEdit() {
      return this.$store.getters.userHasPermissionTo(
        `edit ${this.modelPlurial}`
      );
    }
  },
  methods: {
    confirmActionRecord(type) {
      if (this.blockDelete && type === this.dialogTypes.delete) {
        this.$vs.dialog({
          color: "danger",
          title: "Attention !",
          text: this.blockDeleteMessage,
          acceptText: "OK"
        });
      } else {
        let message = "";
        switch (type) {
          case "archive":
            message = `Voulez vous vraiment archiver ${this.name} ?`;
            break;
          case "delete":
            message = `Voulez vous vraiment supprimer ${this.name} ?`;
            break;
          default:
            message = `Voulez vous vraiment restaurer ${this.name} ?`;
            break;
        }
        if (this.linkedTables) {
          this.dialogFootNote = `De même sera fait pour ${this.linkedTables}`;
        }
        this.dialogType = this.dialogTypes[type];
        this.dialogMessage = message;
        this.dialogOpen = true;
      }
    },
    editRecord() {
      if (this.withPrompt) {
        this.$store.dispatch(
          `${this.model}Management/editItem`,
          this.params.data
        );
      } else {
        this.$router.push(
          `/${this.modelPlurial}/${this.model}-edit/${this.params.data.id}`
        );
      }
    },
    archiveRecord() {
      this.$store
        .dispatch(`${this.model}Management/removeItem`, this.params.data.id)
        .then(response => {
          this.showActionSuccess("archive");
        })
        .catch(err => {
          console.error(err);
          this.showActionError(err);
        });
    },
    restoreRecord() {
      this.$store
        .dispatch(`${this.model}Management/restoreItem`, this.params.data.id)
        .then(response => {
          this.showActionSuccess("restore");
        })
        .catch(err => {
          console.error(err);
          this.showActionError(err);
        });
    },
    deleteRecord() {
      this.$store
        .dispatch(
          `${this.model}Management/${
            this.usesSoftDelete ? "forceRemoveItem" : "removeItem"
          }`,
          this.params.data.id
        )
        .then(response => {
          this.showActionSuccess("delete");
        })
        .catch(err => {
          console.error(err);
          this.showActionError(err);
        });
    },
    showActionSuccess(type) {
      this.$vs.notify({
        color: "success",
        title: "Succès",
        text:
          type === "delete"
            ? `Suppression terminée avec succès`
            : type === "archive"
            ? `Archivage terminé avec succès`
            : `Restauration terminée avec succès`
      });
    },
    showActionError(error) {
      this.$vs.notify({
        color: "danger",
        title: "Erreur",
        text: error.message
      });
    }
  }
};
</script>
