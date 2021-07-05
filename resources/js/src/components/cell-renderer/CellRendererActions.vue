<template>
    <div
        class="flex justify-end"
        :style="{ direction: $vs.rtl ? 'rtl' : 'ltr' }"
        v-if="!disabled"
    >
        <vx-tooltip
            v-if="authorizedTo('edit') && !params.data.deleted_at && canEdit"
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
            v-if="authorizedTo('delete') && usesSoftDelete && canArchive"
            :text="params.data.deleted_at ? 'Restaurer' : 'Archiver'"
            delay=".5s"
            class="mx-2"
        >
            <feather-icon
                icon="ArchiveIcon"
                :svgClasses="this.archiveSvg"
                @click="
                    params.data.deleted_at
                        ? confirmActionRecord(dialogTypes.restore)
                        : confirmActionRecord(dialogTypes.archive)
                "
            />
        </vx-tooltip>
        <vx-tooltip
            v-if="
                authorizedTo('delete') &&
                    (params.data.deleted_at || !usesSoftDelete) &&
                    canDelete
            "
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
        <vx-tooltip
        v-if="isAdmin && this.params.model == 'company'"
            text="Dupliquer"
            delay=".5s"
            class="mx-2"
        >
            <feather-icon
                icon="CopyIcon"
                svgClasses="h-5 w-5 hover:text-danger cursor-pointer"
                @click="confirmActionRecord('duplicate')"
            />
        </vx-tooltip>

        <vs-prompt
            type="confirm"
            :color="dialogColors[dialogType]"
            :title="dialogTitles[dialogType]"
            @cancel="dialogOpen = false"
            @accept="dialogType ? dialogActions[dialogType]() : null"
            :acceptText="dialogAcceptText[dialogType]"
            cancelText="Annuler"
            :active.sync="dialogOpen"
        >
            <div>
                <div>{{ dialogMessage }}</div>
                <small v-if="footNote" class="mt-3">{{ footNote }}</small>
            </div>
        </vs-prompt>
    </div>
</template>

<script>
export default {
  name: 'CellRendererActions',
  data () {
    return {
      dialogOpen: false,
      dialogMessage: '',
      dialogType: '',
      dialogTypes: {
        restore: 'restore',
        archive: 'archive',
        delete: 'delete',
        duplicate: 'duplicate'
      },
      dialogColors: {
        restore: 'success',
        archive: 'warning',
        delete: 'danger',
        duplicate: 'primary'
      },
      dialogTitles: {
        restore: 'Confirmer restauration',
        archive: 'Confirmer archivage',
        delete: 'Confirmer suppression',
        duplicate: 'Confirmer duplication'
      },
      dialogAcceptText: {
        restore: 'Restaurer',
        archive: 'Archiver',
        delete: 'Supprimer',
        duplicate: 'Dupliquer'
      },
      dialogActions: {
        restore: this.restoreRecord,
        archive: this.archiveRecord,
        delete: this.deleteRecord,
        duplicate: this.duplicateRecord
      }
    }
  },
  computed: {
    name () {
      return this.params.name
        ? this.params.name(this.params.data)
        : this.params.data.name
    },
    model () {
      return this.params.model
    },
    modelPlurial () {
      return this.params.modelPlurial
    },
    withPrompt () {
      return this.params.withPrompt
    },
    footNote () {
      return (
        this.params.footNotes && this.params.footNotes[this.dialogType]
      )
    },
    usesSoftDelete () {
      return this.params.usesSoftDelete !== undefined
        ? this.params.usesSoftDelete
        : true
    },
    canEdit () {
      return this.params.canEdit
        ? this.params.canEdit(this.params.data)
        : true
    },
    canArchive () {
      return this.params.canArchive
        ? this.params.canArchive(this.params.data)
        : true
    },
    canDelete () {
      return this.params.canDelete
        ? this.params.canDelete(this.params.data)
        : true
    },
    blockDelete () {
      return this.params.blockDelete
        ? this.params.blockDelete(this.params.data)
        : false
    },
    blockDeleteMessage () {
      return this.params.blockDeleteMessage
        ? this.params.blockDeleteMessage(this.params.data)
        : `Impossible à supprimer ${name}`
    },
    disabled () {
      return this.params.disabled
        ? this.params.disabled(this.params.data)
        : false
    },
    archiveSvg () {
      return this.params.data.deleted_at
        ? 'h-5 w-5 text-success cursor-pointer'
        : 'h-5 w-5 hover:text-warning cursor-pointer'
    },
    isAdmin () {
      return this.$store.getters.AppActiveUser.is_admin
    }
  },
  methods: {
    authorizedTo (action, model = this.modelPlurial) {
      return this.$store.getters.userHasPermissionTo(
        `${action} ${model}`
      )
    },
    onArchive () {
      if (this.params.onArchive) {
        this.params.onArchive(this.params.data)
      }
    },
    onRestore () {
      if (this.params.onRestore) {
        this.params.onRestore(this.params.data)
      }
    },
    onDelete () {
      if (this.params.onDelete) {
        this.params.onDelete(this.params.data)
      }
    },
    onDuplicate () {
      if (this.params.onDuplicate) {
        this.params.onDuplicate(this.params.data)
      }
    },
    confirmActionRecord (type) {
      if (this.blockDelete && type === this.dialogTypes.delete) {
        this.$vs.dialog({
          color: 'danger',
          title: 'Attention !',
          text: this.blockDeleteMessage,
          acceptText: 'OK'
        })
      } else {
        let message = ''
        switch (type) {
        case 'duplicate':
          message = `Voulez vous vraiment dupliquer ${this.name} ?`
          break
        case 'archive':
          message = `Voulez vous vraiment archiver ${this.name} ?`
          break
        case 'delete':
          message = `Voulez vous vraiment supprimer ${this.name} ?`
          break
        default:
          message = `Voulez vous vraiment restaurer ${this.name} ?`
          break
        }
        this.dialogType = this.dialogTypes[type]
        this.dialogMessage = message
        this.dialogOpen = true
      }
    },
    editRecord () {
      if (this.withPrompt) {
        this.$store.dispatch(
          `${this.model}Management/editItem`,
          this.params.data
        )
      } else {
        this.$router.push(
          `/${this.modelPlurial}/${this.model}-edit/${this.params.data.id}`
        )
      }
    },
    archiveRecord () {
      this.$store
        .dispatch(`${this.model}Management/removeItems`, [
          this.params.data.id
        ])
        .then(data => {
          this.showActionSuccess('archive')
          this.onArchive()
        })
        .catch(err => {
          console.error(err)
          this.showActionError(err)
        })
    },
    restoreRecord () {
      this.$store
        .dispatch(`${this.model}Management/restoreItems`, [
          this.params.data.id
        ])
        .then(data => {
          this.showActionSuccess('restore')
          this.onRestore()
        })
        .catch(err => {
          console.error(err)
          this.showActionError(err)
        })
    },
    deleteRecord () {
      this.$store
        .dispatch(
          `${this.model}Management/${
            this.usesSoftDelete ? 'forceRemoveItems' : 'removeItems'
          }`,
          [this.params.data.id]
        )
        .then(data => {
          this.showActionSuccess('delete')
          this.onDelete()
        })
        .catch(err => {
          console.error(err)
          this.showActionError(err)
        })
    },
    duplicateRecord () {
      this.$store
        .dispatch(`${this.model}Management/duplicateItem`, [this.params.data.id])
        .then(data => {
          this.showActionSuccess('duplicate')
          this.onDuplicate()
        })
        .catch(err => {
          console.error(err)
          this.showActionError(err)
        })
    },
    showActionSuccess (type) {
      this.$vs.notify({
        color: 'success',
        title: 'Succès',
        text:
        type === 'delete'
            ? 'Suppression terminée avec succès'
            : type === 'archive'
            ? 'Archivage terminé avec succès'
            : type === 'duplicate'
            ? 'Duplication terminée avec succès'
            : 'Restauration terminée avec succès'
      })
    },
    showActionError (error) {
      this.$vs.notify({
        color: 'danger',
        title: 'Erreur',
        text: error.message
      })
    }
  }
}
</script>
