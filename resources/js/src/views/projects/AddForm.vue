<template>
    <div class="p-3 mb-4 mr-4">
        <vs-button @click="activePrompt = true" class="w-full">
            Ajouter un projet
        </vs-button>
        <vs-prompt
            title="Ajouter un projet"
            accept-text="Ajouter"
            cancel-text="Annuler"
            button-cancel="border"
            @cancel="clearFields"
            @accept="addProject"
            @close="clearFields"
            :is-valid="validateForm"
            :active.sync="activePrompt"
        >
            <div>
                <form autocomplete="off">
                    <div class="vx-row">
                        <div class="vx-col w-full">
                            <div class="vx-row mt-4" v-if="!disabled">
                                <div class="vx-col w-full">
                                    <div class="flex items-end px-3">
                                        <feather-icon
                                            svgClasses="w-6 h-6"
                                            icon="LockIcon"
                                            class="mr-2"
                                        />
                                        <span
                                            class="font-medium text-lg leading-none"
                                            >Admin</span
                                        >
                                    </div>
                                    <vs-divider />
                                    <infinite-select
                                        header="Société"
                                        model="company"
                                        label="name"
                                        v-model="itemLocal.company_id"
                                        @input="cleanCustomerInput"
                                    />
                                    <vs-divider />
                                </div>
                            </div>
                            <vs-input
                                v-validate="'required|max:255'"
                                label="Nom du projet"
                                name="name"
                                class="w-full mb-4"
                                placeholder="Nom"
                                v-model="itemLocal.name"
                                :color="
                                    !errors.has('name') ? 'success' : 'danger'
                                "
                            />
                            <span
                                class="text-danger text-sm"
                                v-show="errors.has('name')"
                                >{{ errors.first("name") }}</span
                            >
                            <vs-col>
                                <vs-row>
                                    <small class="vs-row date-label"
                                        >Couleur</small
                                    >
                                </vs-row>

                                <vs-row class="pb-2 pl-2">
                                    <v-swatches
                                        clas="vs-row"
                                        v-model="itemLocal.color"
                                        :swatches="colors"
                                        swatch-size="40"
                                    ></v-swatches>
                                </vs-row>
                            </vs-col>
                            <div class="my-4">
                                <small class="date-label">
                                    Date de démarrage prévue
                                </small>
                                <datepicker
                                    class="pickadate"
                                    :disabledDates="{
                                        to: new Date(Date.now() - 8640000),
                                        from: new Date(itemLocal.date - 8640000)
                                    }"
                                    :language="langFr"
                                    name="date"
                                    v-model="itemLocal.startDate"
                                    :color="validateForm ? 'success' : 'danger'"
                                >
                                </datepicker>
                            </div>
                            <div class="my-4">
                                <small class="date-label">
                                    Date de livraison prévue
                                </small>
                                <datepicker
                                    class="pickadate"
                                    :disabledDates="{
                                        to: new Date(itemLocal.startDate - 8640000)
                                    }"
                                    :language="langFr"
                                    name="date"
                                    v-model="itemLocal.date"
                                    :color="validateForm ? 'success' : 'danger'"
                                >
                                </datepicker>
                            </div>
                            <div
                                class="my-4"
                                v-if="itemLocal.company_id != null"
                            >
                                <infinite-select
                                    header="Client"
                                    model="customer"
                                    label="name"
                                    v-model="itemLocal.customer_id"
                                    :filters="customerFilters"
                                />
                            </div>
                            <div class="my-4">
                                <file-input
                                    :items="uploadedFiles"
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
import Datepicker from "vuejs-datepicker";
import { fr } from "vuejs-datepicker/src/locale";
import moment from "moment";
import { Validator } from "vee-validate";
import errorMessage from "./errorValidForm";
import InfiniteSelect from "@/components/inputs/selects/InfiniteSelect";

import VSwatches from "vue-swatches";
import "vue-swatches/dist/vue-swatches.css";

import FileInput from "@/components/inputs/FileInput.vue";

import { project_colors } from "../../../themeConfig";

// register custom messages
Validator.localize("fr", errorMessage);

export default {
    components: {
        InfiniteSelect,
        Datepicker,
        VSwatches,
        FileInput
    },
    data() {
        return {
            activePrompt: false,
            langFr: fr,

            itemLocal: {
                name: "",
                startDate: new Date(),
                date: new Date(),
                customer_id: null,
                company_id: !this.isAdmin
                    ? this.$store.state.AppActiveUser.company_id
                    : null,
                company: !this.isAdmin
                    ? this.$store.state.AppActiveUser.company
                    : null,
                color: ""
            },
            colors: project_colors,

            token:
                "token_" +
                Math.random()
                    .toString(36)
                    .substring(2, 15),
            uploadedFiles: []
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
                this.itemLocal.company_id != null
            );
        },
        disabled() {
            const user = this.$store.state.AppActiveUser;
            if (this.isAdmin) {
                return false;
            } else {
                this.itemLocal.company_id = user.company_id;
                return true;
            }
        },
        customerFilters() {
            return {
                company_id: this.itemLocal.company_id
            };
        }
    },
    methods: {
        clearFields(deleteFiles = true) {
            if (deleteFiles) {
                this.deleteFiles();
            }
            this.itemLocal = {
                name: "",
                startDate: new Date(),
                date: new Date(),
                customer_id: null,
                company_id: null,
                company: null,
                color: ""
            };
        },
        addProject() {
            this.$validator.validateAll().then(result => {
                const item = JSON.parse(JSON.stringify(this.itemLocal));
                item.startDate = moment(this.itemLocal.startDate).format("YYYY-MM-DD");
                item.date = moment(this.itemLocal.date).format("YYYY-MM-DD");
                if (this.uploadedFiles.length > 0) {
                    item.token = this.token;
                }

                if (result) {
                    this.$store
                        .dispatch("projectManagement/addItem", item)
                        .then(data => {
                            this.clearFields(false);
                            this.$vs.loading.close();
                            this.$vs.notify({
                                title: "Ajout d'un projet",
                                text: `"${data.payload.name}" ajouté avec succès`,
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
        cleanCustomerInput() {
            this.itemLocal.customer_id = null;
            this.itemLocal.customer = null;
        },
        deleteFiles() {
            const ids = this.uploadedFiles.map(item => item.id);
            if (ids.length > 0) {
                this.$store
                    .dispatch("documentManagement/removeItems", ids)
                    .then(response => {
                        this.uploadedFiles = [];
                    })
                    .catch(error => {});
            }
        }
    },
    mounted() {}
};
</script>
