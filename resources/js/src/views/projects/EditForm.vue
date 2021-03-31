<!-- =========================================================================================
    File Name: TodoEdit.vue
    Description: Edit todo component
    ----------------------------------------------------------------------------------------
    Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
      Author: Pixinvent
    Author URL: http://www.themeforest.net/user/pixinvent
========================================================================================== -->

<template>
    <vs-prompt
        title="Edition d'un projet"
        accept-text="Modifier"
        cancel-text="Annuler"
        button-cancel="border"
        @cancel="init"
        @accept="submitItem"
        @close="init"
        :is-valid="validateForm"
        :active.sync="activePrompt"
    >
        <div>
            <form autocomplete="off">
                <div class="vx-row">
                    <div class="vx-col w-full">
                        <div v-if="isAdmin" class="vx-row mt-4">
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
                                <v-select
                                    label="name"
                                    @input="cleanCustomerInput"
                                    :reduce="company => company.id"
                                    v-model="itemLocal.company_id"
                                    :options="companiesData"
                                    class="w-full"
                                >
                                    <template #header>
                                        <div class="vs-select--label">
                                            Société
                                        </div>
                                    </template>
                                </v-select>
                                <vs-divider />
                            </div>
                        </div>
                        <small class="date-label">Nom du projet</small>
                        <vs-input
                            v-validate="'required|max:255'"
                            name="name"
                            class="w-full mb-4"
                            placeholder="Nom"
                            v-model="itemLocal.name"
                            :color="!errors.has('name') ? 'success' : 'danger'"
                        />
                        <span
                            class="text-danger text-sm"
                            v-show="errors.has('name')"
                            >{{ errors.first("name") }}</span
                        >
                        <vs-col>
                            <vs-row>
                                <small class="vs-row date-label">Couleur</small>
                            </vs-row>

                            <vs-row class="pb-2 pl-2">
                                <v-swatches
                                    v-model="itemLocal.color"
                                    :swatches="colors"
                                    swatch-size="40"
                                ></v-swatches>
                            </vs-row>
                        </vs-col>
                        <div class="my-4">
                            <small class="date-label">
                                Date de livraison prévue
                            </small>
                            <datepicker
                                class="pickadate"
                                :disabledDates="{
                                    to: new Date(Date.now() - 8640000)
                                }"
                                :language="langFr"
                                name="date"
                                v-model="itemLocal.date"
                                :color="validateForm ? 'success' : 'danger'"
                            ></datepicker>
                        </div>
                        <div class="my-4" v-if="itemLocal.company_id != null">
                            <v-select
                                label="name"
                                :reduce="customer => customer.id"
                                v-model="itemLocal.customer_id"
                                :options="customersData"
                                class="w-full"
                            >
                                <template #header>
                                    <div class="vs-select--label">Client</div>
                                </template>
                            </v-select>
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
</template>

<script>
import Datepicker from "vuejs-datepicker";
import { fr } from "vuejs-datepicker/src/locale";
import moment from "moment";
import { Validator } from "vee-validate";
import errorMessage from "./errorValidForm";
import vSelect from "vue-select";

import VSwatches from "vue-swatches";
import "vue-swatches/dist/vue-swatches.css";

import FileInput from "@/components/inputs/FileInput.vue";
import { project_colors } from "../../../themeConfig";

// register custom messages
Validator.localize("fr", errorMessage);

export default {
    components: {
        vSelect,
        Datepicker,
        VSwatches,
        FileInput
    },
    props: {
        itemId: {
            type: Number,
            required: true
        },
        refreshData: {}
    },
    data() {
        return {
            itemLocal: JSON.parse(
                JSON.stringify(
                    this.$store.getters["projectManagement/getItem"](
                        this.itemId
                    )
                )
            ),
            colors: project_colors,
            langFr: fr,
            token:
                "token_" +
                Math.random()
                    .toString(36)
                    .substring(2, 15)
        };
    },
    computed: {
        isAdmin() {
            return this.$store.state.AppActiveUser.is_admin;
        },
        activePrompt: {
            get() {
                return this.itemId && this.itemId > 0 ? true : false;
            },
            set(value) {
                this.$store
                    .dispatch("projectManagement/editItem", {})
                    .then(() => {})
                    .catch(err => {
                        console.error(err);
                    });
            }
        },
        companiesData() {
            return this.$store.getters["companyManagement/getItems"];
        },
        customersData() {
            let customers = this.filterItemsAdmin(
                this.$store.getters["customerManagement/getItems"]
            );

            return customers;
        },
        validateForm() {
            return (
                !this.errors.any() &&
                this.itemLocal.name != "" &&
                this.itemLocal.company_id != null
            );
        }
    },
    methods: {
        init() {
            this.deleteFiles();
            this.itemLocal = Object.assign(
                {},
                this.$store.getters["projectManagement/getItem"](this.itemId)
            );
        },
        submitItem() {
            this.$validator.validateAll().then(result => {
                const item = JSON.parse(JSON.stringify(this.itemLocal));

                item.date = moment(this.itemLocal.date).format("YYYY-MM-DD");
                item.token = this.token;

                this.$store
                    .dispatch("projectManagement/updateItem", item)
                    .then(() => {
                        if (this.refreshData) {
                            this.refreshData();
                        }
                        this.$vs.loading.close();
                        this.$vs.notify({
                            title: "Modification d'un projet",
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
            });
        },
        cleanCustomerInput() {
            this.itemLocal.customer_id = null;
            this.itemLocal.customer = null;
        },
        filterItemsAdmin(items) {
            let filteredItems = [];
            const user = this.$store.state.AppActiveUser;
            if (this.isAdmin) {
                filteredItems = items.filter(
                    item => item.company_id === this.itemLocal.company_id
                );
            } else {
                filteredItems = items.filter(
                    item => item.company_id === user.company_id
                );
            }
            return filteredItems;
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
        }
    },
    mounted() {
        // Parse unselected color
        if (this.itemLocal.color === null) {
            this.itemLocal.color = "";
        }

        // Parse customer label
        if (this.itemLocal.customer) {
            if (this.itemLocal.customer.professional === 0) {
                this.itemLocal.customer.name = this.itemLocal.customer.lastname;
            }
        }
    }
};
</script>
