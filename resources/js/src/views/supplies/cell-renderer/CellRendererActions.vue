<template>
    <div :style="{ direction: $vs.rtl ? 'rtl' : 'ltr' }">
        <feather-icon
            icon="Edit3Icon"
            svgClasses="h-5 w-5 mr-4 hover:text-primary cursor-pointer"
            @click="editRecord"
        />
        <feather-icon
            icon="Trash2Icon"
            svgClasses="h-5 w-5 hover:text-danger cursor-pointer"
            @click="confirmDeleteRecord()"
        />
    </div>
</template>

<script>
var modelTitle = "Approvisionnement";
export default {
    name: "CellRendererActions",
    methods: {
        editRecord() {
            this.$store
                .dispatch("supplyManagement/editItem", this.params.data)
                .then(() => {})
                .catch(err => {
                    console.error(err);
                });
        },
        async confirmDeleteRecord() {

            let tasks = [];
            this.$store
                .dispatch("taskManagement/fetchItems", {
                    supply_id: this.params.data.id
                })
                .then(data => {
                    if (data && data.success) {
                        tasks = data.payload;

                        let message = "";
                     if (tasks.length > 0) {
                            message =
                                "L'approvisionnement " +
                                this.params.data.name +
                                " est utilisée dans une tâche ou plus. Voulez vous vraiment la supprimer ?";
                        }

                        if (message !== "") {
                            this.$vs.dialog({
                                type: "confirm",
                                color: "danger",
                                title: "Confirmer suppression",
                                text: message,
                                accept: this.deleteRecord,
                                acceptText: "Supprimer",
                                cancelText: "Annuler"
                            });
                        } else {
                            this.$vs.dialog({
                                type: "confirm",
                                color: "danger",
                                title: "Confirmer suppression",
                                text:
                                    `Voulez vous vraiment supprimer l'approvisionnement ` +
                                    this.params.data.name +
                                    ` ?`,
                                accept: this.deleteRecord,
                                acceptText: "Supprimer",
                                cancelText: "Annuler"
                            });
                        }
                    }
                })
                .catch(err => {
                    console.error(err);
                });
        },
        deleteRecord() {
            this.$store
                .dispatch(
                    "supplyManagement/forceRemoveItem",
                    this.params.data.id
                )
                .then(() => {
                    this.showDeleteSuccess();
                })
                .catch(err => {
                    console.error(err);
                });
        },
        showDeleteSuccess() {
            this.$vs.notify({
                color: "success",
                title: modelTitle,
                text: `Approvisionnement supprimée`
            });
        }
    }
};
</script>
