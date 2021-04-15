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
var modelTitle = "Compétence";
export default {
    name: "CellRendererActions",
    methods: {
        editRecord() {
            this.$store
                .dispatch("skillManagement/editItem", this.params.data)
                .then(() => {})
                .catch(err => {
                    console.error(err);
                });
        },
        async confirmDeleteRecord() {
            let workareas = this.$store.state.workareaManagement.workareas;

            let haveSkill = [];
            if (workareas) {
                workareas.forEach(w => {
                    if (w.skills) {
                        w.skills.forEach(s => {
                            if (s.id === this.params.data.id) {
                                haveSkill.push({ id: w.id, name: w.name });
                            }
                        });
                    }
                });
            }

            let tasks = [];
            this.$store
                .dispatch("taskManagement/fetchItems", {
                    skill_id: this.params.data.id
                })
                .then(data => {
                    if (data && data.success) {
                        tasks = data.payload;

                        let message = "";
                        if (haveSkill.length > 0 && tasks.length > 0) {
                            message =
                                "La compétence " +
                                this.params.data.name +
                                " est utilisée dans des pôles de productions et des tâches. Voulez vous vraiment la supprimer ?";
                        } else if (tasks.length > 0) {
                            message =
                                "La compétence " +
                                this.params.data.name +
                                " est utilisée dans une tâche ou plus. Voulez vous vraiment la supprimer ?";
                        } else if (haveSkill.length > 0) {
                            message =
                                "La compétence " +
                                this.params.data.name +
                                " est utilisée dans un pôle de production ou plus. Voulez vous vraiment la supprimer ?";
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
                                    `Voulez vous vraiment supprimer la compétence ` +
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
                    "skillManagement/forceRemoveItem",
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
                text: `Compétence supprimé`
            });
        }
    }
};
</script>
