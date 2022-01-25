<template>
    <div class="ml-5">
        <vs-button
            @click="activePrompt = true"
            color="success"
            type="filled"
            size="small"
            >Ajouter une étape</vs-button
        >
        <vs-prompt
            title="Ajouter une étape"
            accept-text="Ajouter"
            cancel-text="Annuler"
            button-cancel="border"
            @cancel="clearFields"
            @accept="addItem"
            @close="clearFields"
            :is-valid="validateForm"
            :active.sync="activePrompt"
            class="task-compose"
        >
            <div>
                <form autocomplete="off" v-on:submit.prevent>
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
                                    placeholder="Ajouter une description"
                                    v-validate="'max:1500'"
                                    counter="1500"
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

                            <!-- <div v-if="itemLocal.skills.length > 0 && workareasDataFiltered.length == 0"> -->
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
                                <small class="date-label">
                                    Temps estimé (en h)
                                </small>
                                <vs-input-number
                                    min="1"
                                    max="200"
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
        company_id: {
            required: true
        }
    },
    data() {
        return {
            activePrompt: false,

            itemLocal: {
                name: "",
                order: 1,
                estimated_time: 1,
                description: "",
                // workarea_id: null,
                skills: [],
                documents: []
            },

            token:
                "token_" +
                Math.random()
                    .toString(36)
                    .substring(2, 15),

            workareasDataFiltered: []
        };
    },
    computed: {
        validateForm() {
            return (
                !this.errors.any() &&
                this.itemLocal.name != "" &&
                this.itemLocal.order != "" &&
                this.itemLocal.estimated_time != "" &&
                this.itemLocal.skills.length > 0
            );
        },
        workareasData() {
            let workareasData = this.$store.state.workareaManagement.workareas;
            let filteredItems = this.filterItemsAdmin(workareasData);
            return filteredItems;
        },
        skillsData() {
            let skillsData = this.$store.getters["skillManagement/getItems"];
            return this.filterItemsAdmin(skillsData);
        }
    },
    methods: {
        clearFields() {
            this.deleteFiles();
            Object.assign(this.itemLocal, {
                name: "",
                order: 1,
                estimated_time: 1,
                description: "",
                //workarea_id: null,
                skills: [],
                documents: []
            });
            Object.assign(this.workareasDataFiltered, []);
        },
        addItem() {
            this.$validator.validateAll().then(result => {
                // this.itemLocal.workarea = this.workareasDataFiltered.find(
                //   workarea => workarea.id === this.itemLocal.workarea_id
                // );

                if (result) {
                    const item = JSON.parse(JSON.stringify(this.itemLocal));
                    if (this.itemLocal.documents.length > 0) {
                        item.token = this.token;
                    }
                    this.$store
                        .dispatch("repetitiveTaskManagement/addItem", item)
                        .then(() => {
                            Object.assign(this.itemLocal, {
                                name: "",
                                order: 1,
                                estimated_time: 1,
                                description: "",
                                //workarea_id: null,
                                skills: [],
                                documents: []
                            });
                            Object.assign(this.workareasDataFiltered, []);
                            this.$vs.loading.close();
                            this.$vs.notify({
                                title: "Ajout d'une étape",
                                text: `"${this.itemLocal.name}" ajouté avec succès`,
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
            const ids = this.itemLocal.documents.map(item => item.id);
            if (ids.length > 0) {
                this.$store
                    .dispatch("documentManagement/removeItems", ids)
                    .then(response => {
                        this.itemLocal.documents = [];
                    })
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
                    item => item.company_id === this.company_id
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
.inputNumber {
    justify-content: start;
    max-width: 97px;
}
</style>
