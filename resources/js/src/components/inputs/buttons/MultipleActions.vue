<template>
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
            <vs-dropdown-item v-if="canDelete" @click="confirmAction('delete')">
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
        }
    },
    data() {
        return {
            restore: {
                color: "success",
                title: "restauration",
                action: "restoreItems",
                displayAction: "restaurer"
            },
            archive: {
                color: "warning",
                title: "archivage",
                action: "removeItems",
                displayAction: "archiver"
            },
            delete: {
                color: "danger",
                title: "suppression",
                action: this.usesSoftDelete
                    ? "forceRemoveItems"
                    : "removeItems",
                displayAction: "supprimer"
            }
        };
    },
    computed: {
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
                [type]: { color, title, displayAction }
            } = this;

            if (items.length === 0) {
                this.$vs.notify({
                    color: "danger",
                    title: `${title} impossible`,
                    text: "Veuillez sélectionner au moins un élément"
                });
                return;
            }

            const multiple = items.length !== 1;
            this.$vs.dialog({
                color,
                title: `Confirmer ${title}`,
                text: `Voulez vous vraiment ${displayAction} ce${
                    multiple ? `s ${items.length} ` : "t "
                } élément${items.length === 1 ? "" : "s"} ?`,
                acceptText: displayAction,
                accept: () => this.performAction(type)
            });
        },
        async performAction(type) {
            const {
                items,
                [type]: { title, action }
            } = this;

            try {
                await this.$store.dispatch(
                    `${this.model}Management/${action}`,
                    items.map(item => item.id)
                );

                this.$emit("on-action");

                this.$vs.notify({
                    color: "success",
                    title: "Succès",
                    text: `${title} terminé${
                        type !== "archive" ? "e" : ""
                    } avec succès`
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
