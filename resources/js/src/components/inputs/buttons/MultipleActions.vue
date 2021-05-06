<template>
    <div>
        <vs-dropdown vs-trigger-click class="cursor-pointer">
            <div
                class="p-3 shadow-drop rounded-lg d-theme-dark-light-bg cursor-pointer flex items-end justify-center text-lg font-medium w-32"
            >
                <span class="mr-2 leading-none">Actions</span>
                <feather-icon icon="ChevronDownIcon" svgClasses="h-4 w-4" />
            </div>

            <vs-dropdown-menu>
                <vs-dropdown-item
                    v-if="canRestore"
                    @click="confirmAction('restore')"
                >
                    <span class="flex items-center">
                        <feather-icon
                            icon="ArchiveIcon"
                            svgClasses="h-4 w-4"
                            class="mr-2"
                        />
                        <span>Restaurer</span>
                    </span>
                </vs-dropdown-item>

                <vs-dropdown-item
                    v-if="canArchive"
                    @click="confirmAction('archive')"
                >
                    <span class="flex items-center">
                        <feather-icon
                            icon="ArchiveIcon"
                            svgClasses="h-4 w-4"
                            class="mr-2"
                        />
                        <span>Archiver</span>
                    </span>
                </vs-dropdown-item>
                <vs-dropdown-item
                    v-if="canDelete"
                    @click="confirmAction('delete')"
                >
                    <span class="flex items-center">
                        <feather-icon
                            icon="TrashIcon"
                            svgClasses="h-4 w-4"
                            class="mr-2"
                        />
                        <span>Supprimer</span>
                    </span>
                </vs-dropdown-item>
            </vs-dropdown-menu>
        </vs-dropdown>

        <vs-prompt
            type="confirm"
            :active.sync="dialogOpen"
            :color="dialogColors[dialogType]"
            :title="`Confirmer ${dialogTitles[dialogType]}`"
            :acceptText="dialogDisplayActions[dialogType]"
            cancelText="Annuler"
            @accept="performAction"
            @cancel="dialogOpen = false"
        >
            <div>
                <div>{{ dialogText }}</div>
                <small v-if="footNote" class="mt-3">{{ footNote }}</small>
            </div>
        </vs-prompt>
    </div>
</template>

<script>
export default {
    props: {
        model: {
            type: String,
            required: true
        },
        modelPlurial: {
            type: String,
            required: true
        },
        items: {
            type: Array,
            default: []
        },
        usesSoftDelete: {
            type: Boolean,
            default: true
        },
        footNotes: {
            type: Object,
            default: null
        }
    },
    data() {
        return {
            dialogType: "",
            dialogOpen: false,
            dialogColors: {
                restore: "success",
                archive: "warning",
                delete: "danger"
            },
            dialogTitles: {
                restore: "restauration",
                archive: "archivage",
                delete: "suppression"
            },
            dialogActions: {
                restore: "restoreItems",
                archive: "removeItems",
                delete: this.usesSoftDelete ? "forceRemoveItems" : "removeItems"
            },
            dialogDisplayActions: {
                restore: "restaurer",
                archive: "archiver",
                delete: "supprimer"
            }
        };
    },
    computed: {
        dialogText() {
            const {
                items,
                dialogDisplayActions: { [this.dialogType]: displayAction }
            } = this;
            const multiple = items.length !== 1;

            return `Voulez vous vraiment ${displayAction} ce${
                multiple ? `s ${items.length} ` : "t "
            } élément${items.length === 1 ? "" : "s"} ?`;
        },
        footNote() {
            return (
                this.footNotes &&
                this.footNotes[this.dialogType] &&
                this.footNotes[this.dialogType](this.items.length > 1)
            );
        },
        canRestore() {
            return (
                this.usesSoftDelete &&
                this.authorizedTo("delete") &&
                this.items.length > 0 &&
                this.items.every(item => item.deleted_at)
            );
        },
        canArchive() {
            return (
                this.usesSoftDelete &&
                this.authorizedTo("delete") &&
                this.items.length > 0 &&
                this.items.every(item => !item.deleted_at)
            );
        },
        canDelete() {
            return this.authorizedTo("delete");
        }
    },
    methods: {
        authorizedTo(action, model = this.modelPlurial) {
            return this.$store.getters.userHasPermissionTo(
                `${action} ${model}`
            );
        },
        confirmAction(type) {
            const {
                items,
                dialogTitles: { [type]: title }
            } = this;

            if (items.length === 0) {
                this.$vs.notify({
                    color: "danger",
                    title: `${title} impossible`,
                    text: "Veuillez sélectionner au moins un élément"
                });
                return;
            }

            this.dialogType = type;
            this.dialogOpen = true;
        },
        async performAction() {
            const {
                items,
                dialogActions: { [this.dialogType]: action }
            } = this;

            try {
                const response = await this.$store.dispatch(
                    `${this.model}Management/${action}`,
                    items.map(item => item.id)
                );

                this.$emit("on-action");

                this.$vs.notify({
                    color: "success",
                    title: "Succès",
                    text: response.message
                });
            } catch (error) {
                this.$vs.notify({
                    color: "danger",
                    title: "Erreur",
                    text: error.message
                });
            }
        }
    }
};
</script>

<style>
.vs-dialog footer .vs-button-text::first-letter,
.con-text-noti h3::first-letter,
.con-text-noti p::first-letter {
    text-transform: uppercase;
}
</style>
