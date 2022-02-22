<!-- =========================================================================================
  File Name: BugEdit.vue
  Description: bug Edit Page
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/bug/pixinvent
========================================================================================== -->

<template>
    <div id="page-bug-edit">
        <vs-alert
            color="danger"
            title="bug Not Found"
            :active.sync="bug_not_found"
        >
            <span>Le bug {{ $route.params.id }} est introuvable.</span>
            <span>
                <span>voir</span>
                <router-link
                    :to="{ name: 'bugs' }"
                    class="text-inherit underline"
                >
                    Tout les bugs
                </router-link>
            </span>
        </vs-alert>

        <vx-card v-if="bug_data">
            <div slot="no-body" class="tabs-container px-6 pt-6">
                <vs-row vs-type="flex" vs-w="12" class="px-6 justify-end">
                    <vs-button
                        @click="() => confirmDeleteRecord(bug_data.id)"
                        color="danger"
                        type="filled"
                        size="small"
                    >
                        Supprimer le bug
                    </vs-button>
                </vs-row>
                <vs-row vs-justify="left" vs-type="flex" vs-w="12" class="px-6">
                    <h2>
                        Bug n° {{ bug_data.id }} remonté par
                        <span style="font-weight: 900;">
                            {{
                                bug_data.created_by.lastname +
                                    " " +
                                    bug_data.created_by.firstname
                            }}
                        </span>
                        , le {{ dateFormatter(bug_data.created_at) }}
                    </h2>
                </vs-row>
                <vs-row vs-justify="left" vs-type="flex" vs-w="12" class="px-6">
                    <vs-col
                        vs-w="6"
                        vs-xs="12"
                        class="mt-6"
                        v-if="bug_data && bug_data.company"
                    >
                        <p>Société : {{ bug_data.company.name }}</p>
                    </vs-col>
                    <vs-col
                        vs-w="6"
                        vs-xs="12"
                        class="mt-6"
                        v-if="bug_data && bug_data.role"
                    >
                        <p>Rôle : {{ bug_data.role.name }}</p>
                    </vs-col>
                </vs-row>
                <vs-row vs-justify="center" vs-type="flex" vs-w="12">
                    <vs-col vs-w="6" vs-xs="12" class="mt-6 px-6">
                        <simple-select
                            required
                            header="Module"
                            label="name"
                            v-model="bug_data.module"
                            :reduce="item => item.name"
                            :options="modulesOption"
                        />
                    </vs-col>
                    <vs-col vs-w="6" vs-xs="12" class="mt-6 px-6">
                        <simple-select
                            required
                            header="Type d'erreur"
                            label="name"
                            v-model="bug_data.type"
                            :reduce="item => item.name"
                            :options="typesOption"
                        />
                    </vs-col>
                </vs-row>

                <vs-row vs-justify="left" vs-type="flex" vs-w="12" class="px-6">
                    <vs-textarea
                        class="w-full mt-4"
                        rows="5"
                        label="Ajouter description"
                        v-model="bug_data.description"
                        v-validate="'max:1500'"
                        name="description"
                    />
                    <span
                        class="text-danger text-sm"
                        v-show="errors.has('description')"
                        >{{ errors.first("description") }}</span
                    >
                    <div class="mt-4">
                        <file-input
                            :items="bug_data.documents"
                            :token="token"
                        />
                    </div>
                </vs-row>

                <vs-row vs-justify="right" vs-type="flex" vs-w="12">
                    <vs-col vs-w="4" vs-xs="12" class="mt-6 px-6">
                        <simple-select
                            required
                            header="Statut"
                            label="name"
                            v-model="bug_data.status"
                            :reduce="item => item.name"
                            :options="statusOption"
                        />
                    </vs-col>
                </vs-row>
            </div>
            <!-- Save & Reset Button -->
            <div class="vx-row">
                <div class="vx-col w-full">
                    <div class="mt-8 flex flex-wrap items-center justify-end">
                        <vs-button
                            class="ml-auto mt-2"
                            @click="save_changes"
                            :disabled="!validateForm"
                        >
                            Modifier
                        </vs-button>
                        <vs-button
                            class="ml-4 mt-2"
                            type="border"
                            color="warning"
                            @click="back"
                        >
                            Annuler
                        </vs-button>
                    </div>
                </div>
            </div>
        </vx-card>
    </div>
</template>

<script>
import lodash from "lodash";
// Store Module
import moduleManagement from "@/store/bug-management/moduleBugManagement.js";
import moduleDocumentManagement from "@/store/document-management/moduleDocumentManagement.js";
import SimpleSelect from "@/components/inputs/selects/SimpleSelect.vue";
import moment from "moment";
import { Validator } from "vee-validate";
import FileInput from "@/components/inputs/FileInput.vue";
import errorMessage from "./errorValidForm";

// register custom messages
Validator.localize("fr", errorMessage);
var model = "bug";
var modelPlurial = "bugs";
var modelTitle = "Bug";

export default {
    components: {
        SimpleSelect,
        FileInput
    },
    data() {
        return {
            bug_data: null,
            token:
                "token_" +
                Math.random()
                    .toString(36)
                    .substring(2, 15),

            bug_not_found: false,
            modulesOption: [
                { name: "Tableau de bord" },
                { name: "Projets" },
                { name: "Gammes" },
                { name: "Utilisateurs" },
                { name: "Pôles de production" },
                { name: "Planning" },
                { name: "Rôles" },
                { name: "Compétences" },
                { name: "Sociétés" },
                { name: "Clients" },
                { name: "Heures" },
                { name: "Modules" },
                { name: "Gestion du profil" },
                { name: "Autre..." }
            ],
            typesOption: [
                { name: "Erreur 500" },
                { name: "Erreur 4O4" },
                { name: "Permission denied" },
                { name: "Autre..." }
            ],
            statusOption: [
                { name: "new" },
                { name: "open" },
                { name: "fixed" },
                { name: "retest" }
            ]
        };
    },
    computed: {
        isAdmin() {
            return this.$store.state.AppActiveUser.is_admin;
        },
        disabled() {
            const user = this.$store.state.AppActiveUser;
            if (this.isAdmin) {
                return false;
            }
            return true;
        },
        validateForm() {
            return !this.errors.any();
        }
    },
    methods: {
        fetch_data(id) {
            this.$store
                .dispatch("bugManagement/fetchItem", id)
                .then(data => {
                    this.bug_data = data.payload;
                })
                .catch(err => {
                    if (err.response.status === 404) {
                        this.bug_not_found = true;
                        return;
                    }
                    console.error(err);
                });
        },
        save_changes() {
            /* eslint-disable */
            if (!this.validateForm) return;
            this.$vs.loading();

            const payload = { ...this.bug_data };
            payload.token = this.token;

            this.$store
                .dispatch("bugManagement/updateItem", payload)
                .then(response => {
                    this.$vs.loading.close();
                    this.$vs.notify({
                        title: "Modification",
                        text: "Bug modifier avec succès",
                        iconPack: "feather",
                        icon: "icon-alert-circle",
                        color: "success"
                    });
                    this.$router.push(`/${modelPlurial}`).catch(() => {});
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
        },

        back() {
            this.$router.push(`/${modelPlurial}`).catch(() => {});
        },
        capitalizeFirstLetter(word) {
            if (typeof word !== "string") return "";
            return word.charAt(0).toUpperCase() + word.slice(1);
        },
        dateFormatter(date) {
            return moment(date).format("DD MMMM YYYY");
        },
        deleteFiles() {
            const ids = this.bug_data.documents
                .filter(item => item.token)
                .map(item => item.id);
            if (ids.length > 0) {
                this.$store
                    .dispatch("documentManagement/removeItems", ids)
                    .catch(error => {});
            }
        },
        confirmDeleteRecord(id) {
            this.$vs.dialog({
                type: "confirm",
                color: "danger",
                title: "Confirmer suppression",
                text: `Voulez vous vraiment supprimer le bug ${id} ?`,
                accept: this.deleteRecord,
                acceptText: "Supprimer",
                cancelText: "Annuler"
            });
        },
        deleteRecord() {
            this.$vs.loading();
            this.$store
                .dispatch("bugManagement/removeItems", [this.bug_data.id])
                .then(data => {
                    this.$vs.loading.close();
                    this.showDeleteSuccess();
                })
                .catch(err => {
                    this.$vs.loading.close();
                    console.error(err);
                });
        },
        showDeleteSuccess() {
            this.$vs.loading.close();
            this.$vs.notify({
                title: "Modification",
                text: "Bug modifier avec succès",
                iconPack: "feather",
                icon: "icon-alert-circle",
                color: "success"
            });
            this.$router.push(`/${modelPlurial}`).catch(() => {});
        }
    },
    created() {
        if (!this.isAdmin) {
            this.$router.push(`/${modelPlurial}/${model}-add/`).catch(() => {});
        } else {
            // Register bugManagement Module
            if (!moduleManagement.isRegistered) {
                this.$store.registerModule("bugManagement", moduleManagement);
                moduleManagement.isRegistered = true;
            }
            this.$store.dispatch("bugManagement/fetchItems").catch(err => {
                console.error(err);
            });

            if (!moduleDocumentManagement.isRegistered) {
                this.$store.registerModule(
                    "documentManagement",
                    moduleDocumentManagement
                );
                moduleDocumentManagement.isRegistered = true;
            }

            this.fetch_data(this.$route.params.id);
        }
    },
    beforeDestroy() {
        moduleDocumentManagement.isRegistered = false;
        moduleManagement.isRegistered = false;
        this.$store.unregisterModule("bugManagement");
        this.$store.unregisterModule("documentManagement");
    }
};
</script>
