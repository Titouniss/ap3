<template>
    <div class="p-3 mb-4 mr-4">
        <vs-prompt
            title="Modifier une tâche"
            accept-text="Modifier"
            cancel-text="Annuler"
            button-cancel="border"
            @cancel="clear"
            @accept="submitItem"
            @close="clear"
            :is-valid="validateForm"
            :active.sync="activePrompt"
            class="task-compose"
        >
            <div>
                <form class="editTaskForm" autocomplete="off" v-on:submit.prevent>
                    <div class="vx-row">
                        <!-- Left -->
                        <div
                            class="vx-col flex-1"
                            style="border-right: 1px solid #d6d6d6"
                        >
                            <div class="mb-4">
                                <small class="date-label">Ordre</small>
                                <vs-input-number
                                    min="1"
                                    name="order"
                                    class="inputNumber"
                                    v-model="itemLocal.order"
                                />
                            </div>
                            <vs-input
                                v-validate="'required'"
                                name="stepName"
                                class="w-full mb-4 mt-1"
                                placeholder="Intitulé"
                                v-model="itemLocal.name"
                                :color="
                                    !errors.has('stepName')
                                        ? 'success'
                                        : 'danger'
                                "
                            />
                            <span
                                class="text-danger text-sm"
                                v-show="errors.has('stepName')"
                                >{{ errors.first("stepName") }}</span
                            >

                            <div class="my-4">
                                <small class="date-label">Description</small>
                                <vs-textarea
                                    rows="3"
                                    label="Ajouter une description"
                                    name="description"
                                    class="w-full mb-1 mt-1"
                                    v-model="itemLocal.description"
                                />
                            </div>
                        </div>
                        <!-- Right -->
                        <div class="vx-col flex-1">
                            <simple-select
                                required
                                header="Compétences"
                                label="name"
                                multiple
                                v-model="itemLocal.skills"
                                :reduce="item => item.id"
                                :options="skillsData"
                                @input="updateWorkareasList"
                            />

                            <span
                                v-if="
                                    itemLocal.skills.length > 0 &&
                                        workareasDataFiltered.length == 0
                                "
                                class="text-danger text-sm"
                            >
                                Attention, aucun pôle de production ne possède
                                cette combinaison de compétences
                            </span>

                            <div class="my-4">
                                <small class="date-label"
                                    >Temps estimé (en h)</small
                                >
                                <vs-input-number
                                    min="1"
                                    name="estimatedTime"
                                    class="inputNumber"
                                    v-model="itemLocal.estimated_time"
                                />
                            </div>
                            <div class="my-4">
                                <file-input
                                    :items="itemLocal.documents"
                                    :token="token"
                                />
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </vs-prompt>
    </div>
</template>

<script>
import { Validator } from "vee-validate";
import errorMessage from "./errorValidForm";

import SimpleSelect from "@/components/inputs/selects/SimpleSelect";
import FileInput from "@/components/inputs/FileInput.vue";

// register custom messages
Validator.localize("fr", errorMessage);

export default {
    components: {
        SimpleSelect,
        FileInput
    },
    props: {
        itemId: {
            type: [Number, String],
            required: true
        },
        companyId: {
            type: Number,
            required: true
        }
    },
    data() {
        const item = JSON.parse(
            JSON.stringify(
                this.$store.getters["repetitiveTaskManagement/getItem"](
                    this.itemId
                )
            )
        );
        return {
            itemLocal: item,
            token:
                item.token ||
                "token_" +
                    Math.random()
                        .toString(36)
                        .substring(2, 15),
            workareasDataFiltered: []
        };
    },
    computed: {
        isAdmin() {
            return this.$store.state.AppActiveUser.is_admin;
        },
        validateForm() {
            return (
                !this.errors.any() &&
                this.itemLocal.name != "" &&
                this.itemLocal.order != "" &&
                this.itemLocal.estimated_time != "" &&
                this.itemLocal.skills.length > 0
            );
        },
        activePrompt: {
            get() {
                return this.itemId ? true : false;
            },
            set(value) {
                this.$store
                    .dispatch("repetitiveTaskManagement/editItem", {})
                    .then(() => {})
                    .catch(err => {
                        console.error(err);
                    });
            }
        },
        workareasData() {
            let $workareasData = this.$store.state.workareaManagement.workareas;
            let $filteredItems = this.filterItemsAdmin($workareasData);
            return $filteredItems;
        },
        skillsData() {
            let $skillsData = this.$store.state.skillManagement.skills;
            this.updateWorkareasList(this.itemLocal.skills);
            return this.filterItemsAdmin($skillsData);
        }
    },
    methods: {
        clear() {
            this.deleteFiles();
            this.itemLocal = {};
            this.workareasDataFiltered = [];
        },
        submitItem() {
            this.$validator.validateAll().then(result => {
                //this.itemLocal.workarea = this.workareasDataFiltered.find((workarea) => workarea.id === this.itemLocal.workarea_id)

                if (result) {
                    const item = JSON.parse(JSON.stringify(this.itemLocal));
                    item.token = this.token;
                    this.$store
                        .dispatch("repetitiveTaskManagement/updateItem", item)
                        .then(() => {
                            this.$vs.loading.close();
                            this.$vs.notify({
                                title: "Modification d'une étape",
                                text: `"${this.itemLocal.name}" modifiée avec succès`,
                                iconPack: "feather",
                                icon: "icon-alert-circle",
                                color: "success"
                            });
                        })
                        .catch(error => {
                            this.$vs.loading.close();
                            this.$vs.notify({
                                title: "Error",
                                text: error.message,
                                iconPack: "feather",
                                icon: "icon-alert-circle",
                                color: "danger"
                            });
                        });
                }
            });
        },
        deleteFiles() {
            const ids = this.itemLocal.documents
                .filter(item => item.token)
                .map(item => item.id);
            if (ids.length > 0) {
                this.$store
                    .dispatch("documentManagement/removeItems", ids)
                    .catch(error => {});
            }
        },
        updateWorkareasList(ids) {
            this.workareasDataFiltered = this.workareasData.filter(function(
                workarea
            ) {
                for (let i = 0; i < ids.length; i++) {
                    if (
                        workarea.skills.filter(skill => skill.id == ids[i])
                            .length == 0
                    ) {
                        return false;
                    }
                }
                return true;
            });
            if (!this.workareasDataFiltered.length > 0) {
                this.itemLocal.workarea_id = null;
            }
        },
        filterItemsAdmin(items) {
            let filteredItems = items;
            const user = this.$store.state.AppActiveUser;
            if (this.isAdmin) {
                filteredItems = items.filter(
                    item => item.company_id === this.companyId
                );
            }
            return filteredItems;
        }
    }
};
</script>
<style>
.con-vs-dialog.task-compose .vs-dialog {
    max-width: 700px;
}
.editTaskForm {
    max-height: 450px;
    overflow-y: auto;
    overflow-x: hidden;
}
.inputNumber {
    justify-content: start;
    max-width: 97px;
}
</style>
