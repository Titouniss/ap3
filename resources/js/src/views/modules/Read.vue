<template>
    <div id="page-module-view">
        <router-link
            :to="'/modules'"
            class="flex items-center cursor-pointer text-inherit hover:text-primary pt-3 mb-3"
        >
            <feather-icon class="'h-5 w-5" icon="ArrowLeftIcon"></feather-icon>
            <span class="ml-2"> Retour à la liste des modules </span>
        </router-link>
        <vs-row
            v-if="item"
            vs-type="flex"
            vs-justify="center"
            vs-align="center"
            vs-w="12"
        >
            <vs-col
                vs-type="flex"
                vs-justify="center"
                vs-align="center"
                vs-w="12"
            >
                <vx-card>
                    <form-wizard
                        :title="null"
                        :subtitle="null"
                        @on-validate="onValidateStep"
                        nextButtonText="Suivant"
                        backButtonText="Précédent"
                        finishButtonText="Valider"
                        color="rgba(var(--vs-primary), 1)"
                        errorColor="rgba(var(--vs-danger), 1)"
                    >
                        <tab-content
                            title="Paramètres de connexion"
                            icon="feather icon-database"
                            :before-change="validateStep1"
                        >
                            <!-- Sql configuration -->
                            <form v-if="sqlMode" data-vv-scope="step-1">
                                <vs-row
                                    vs-type="flex"
                                    vs-justify="center"
                                    vs-align="center"
                                    vs-w="12"
                                    class="pb-3"
                                >
                                    <vs-col
                                        vs-type="flex"
                                        vs-justify="center"
                                        vs-align="center"
                                        vs-w="4"
                                        class="px-2"
                                    >
                                        <vs-input
                                            v-validate="'required|max:255'"
                                            name="host"
                                            label-placeholder="Hôte"
                                            v-model="dbConnection.host"
                                            class="w-full"
                                            :success="
                                                dbConnection.host.length > 0 &&
                                                    !errors.has('step-1.host')
                                            "
                                            :danger="errors.has('step-1.host')"
                                            :danger-text="
                                                errors.first('step-1.host')
                                            "
                                        />
                                    </vs-col>
                                    <vs-col
                                        vs-type="flex"
                                        vs-justify="center"
                                        vs-align="center"
                                        vs-w="4"
                                        class="px-2"
                                    >
                                        <vs-input
                                            v-validate="'numeric|max:5'"
                                            name="port"
                                            label-placeholder="Port"
                                            v-model="dbConnection.port"
                                            class="w-full"
                                            :success="
                                                hasClickedNext &&
                                                    !errors.has('step-1.port')
                                            "
                                            :danger="errors.has('step-1.port')"
                                            :danger-text="
                                                errors.first('step-1.port')
                                            "
                                        />
                                    </vs-col>
                                    <vs-col
                                        vs-type="flex"
                                        vs-justify="center"
                                        vs-align="center"
                                        vs-w="4"
                                        class="px-2"
                                    >
                                        <vs-input
                                            v-validate="'required|max:255'"
                                            name="database"
                                            label-placeholder="Base de données"
                                            v-model="dbConnection.database"
                                            class="w-full"
                                            :success="
                                                dbConnection.database.length >
                                                    0 &&
                                                    !errors.has(
                                                        'step-1.database'
                                                    )
                                            "
                                            :danger="
                                                errors.has('step-1.database')
                                            "
                                            :danger-text="
                                                errors.first('step-1.database')
                                            "
                                        />
                                    </vs-col>
                                </vs-row>

                                <vs-row
                                    vs-type="flex"
                                    vs-justify="center"
                                    vs-align="center"
                                    vs-w="12"
                                    class="py-3"
                                >
                                    <vs-col
                                        vs-type="flex"
                                        vs-justify="center"
                                        vs-align="center"
                                        vs-w="4"
                                        class="px-2"
                                    >
                                        <vs-input
                                            v-validate="'required|max:255'"
                                            name="username"
                                            label-placeholder="Utilisateur"
                                            v-model="dbConnection.username"
                                            class="w-full"
                                            :success="
                                                dbConnection.username.length >
                                                    0 &&
                                                    !errors.has(
                                                        'step-1.username'
                                                    )
                                            "
                                            :danger="
                                                errors.has('step-1.username')
                                            "
                                            :danger-text="
                                                errors.first('step-1.username')
                                            "
                                        />
                                    </vs-col>
                                    <vs-col
                                        vs-type="flex"
                                        vs-justify="center"
                                        vs-align="center"
                                        vs-w="4"
                                        class="px-2"
                                    >
                                        <vs-input
                                            ref="password"
                                            type="password"
                                            v-validate="'required|min:8|max:50'"
                                            name="password"
                                            label-placeholder="Mot de passe"
                                            v-model="dbConnection.password"
                                            class="w-full"
                                            :success="
                                                dbConnection.password.length >
                                                    0 &&
                                                    !errors.has(
                                                        'step-1.password'
                                                    )
                                            "
                                            :danger="
                                                errors.has('step-1.password')
                                            "
                                            :danger-text="
                                                errors.first('step-1.password')
                                            "
                                        />
                                    </vs-col>
                                    <vs-col
                                        vs-type="flex"
                                        vs-justify="center"
                                        vs-align="center"
                                        vs-w="4"
                                        class="px-2"
                                    >
                                        <vs-input
                                            type="password"
                                            v-validate="
                                                'required|min:8|max:50|confirmed:password'
                                            "
                                            name="confirm_password"
                                            label-placeholder="Confirmation mot de passe"
                                            v-model="
                                                dbConnection.confirm_password
                                            "
                                            class="w-full"
                                            :success="
                                                dbConnection.confirm_password
                                                    .length > 0 &&
                                                    !errors.has(
                                                        'step-1.confirm_password'
                                                    )
                                            "
                                            :danger="
                                                errors.has(
                                                    'step-1.confirm_password'
                                                )
                                            "
                                            :danger-text="
                                                errors.first(
                                                    'step-1.confirm_password'
                                                )
                                            "
                                        />
                                    </vs-col>
                                </vs-row>

                                <vs-row
                                    vs-type="flex"
                                    vs-justify="center"
                                    vs-align="center"
                                    vs-w="12"
                                    class="pt-3"
                                >
                                    <vs-col
                                        vs-type="flex"
                                        vs-justify="center"
                                        vs-align="center"
                                        vs-w="12"
                                    >
                                        <vs-radio
                                            class="mx-3"
                                            v-model="dbConnection.type"
                                            vs-value="mysql"
                                        >
                                            MySQL
                                        </vs-radio>
                                        <vs-radio
                                            class="mx-3"
                                            v-model="dbConnection.type"
                                            vs-value="sqlite"
                                        >
                                            SQLite
                                        </vs-radio>
                                        <vs-radio
                                            class="mx-3"
                                            v-model="dbConnection.type"
                                            vs-value="pgsql"
                                        >
                                            PostgreSQL
                                        </vs-radio>
                                        <vs-radio
                                            class="mx-3"
                                            v-model="dbConnection.type"
                                            vs-value="sqlsrv"
                                        >
                                            SQL Server
                                        </vs-radio>
                                    </vs-col>
                                </vs-row>
                            </form>

                            <!-- Api configuration -->
                            <form v-if="!sqlMode" data-vv-scope="step-1">
                                <vs-row
                                    vs-type="flex"
                                    vs-justify="center"
                                    vs-align="center"
                                    vs-w="12"
                                    class="pb-3"
                                >
                                    <vs-col
                                        vs-type="flex"
                                        vs-justify="center"
                                        vs-align="center"
                                        vs-w="6"
                                        class="px-2"
                                    >
                                        <vs-input
                                            v-validate="'required|max:255'"
                                            name="url"
                                            label-placeholder="Url"
                                            v-model="apiConnection.url"
                                            class="w-full"
                                            :success="
                                                apiConnection.url.length > 0 &&
                                                    !errors.has('step-1.url')
                                            "
                                            :danger="errors.has('step-1.url')"
                                            :danger-text="
                                                errors.first('step-1.url')
                                            "
                                        />
                                    </vs-col>
                                    <vs-col
                                        vs-type="flex"
                                        vs-justify="center"
                                        vs-align="center"
                                        vs-w="6"
                                        class="px-2"
                                    >
                                        <vs-input
                                            v-validate="'max:255'"
                                            name="authHeaders"
                                            label-placeholder="Entêtes d'authentification"
                                            v-model="apiConnection.authHeaders"
                                            class="w-full"
                                            :success="
                                                hasClickedNext &&
                                                    !errors.has(
                                                        'step-1.authHeaders'
                                                    )
                                            "
                                            :danger="
                                                errors.has('step-1.authHeaders')
                                            "
                                            :danger-text="
                                                errors.first(
                                                    'step-1.authHeaders'
                                                )
                                            "
                                        />
                                    </vs-col>
                                </vs-row>
                            </form>
                        </tab-content>

                        <!-- Table configuration -->
                        <tab-content
                            title="Types de données"
                            icon="feather icon-hard-drive"
                        >
                            <form data-vv-scope="step-2"></form>
                        </tab-content>
                    </form-wizard>
                </vx-card>
            </vs-col>
        </vs-row>
    </div>
</template>

<script>
import { Validator } from "vee-validate";
import { FormWizard, TabContent } from "vue-form-wizard";
import "vue-form-wizard/dist/vue-form-wizard.min.css";

import errorMessage from "./errorValidForm";
Validator.localize("fr", errorMessage);

import moduleModuleManagement from "@/store/module-management/moduleModuleManagement.js";

export default {
    components: {
        FormWizard,
        TabContent
    },
    data() {
        return {
            item: null,
            dbConnection: {
                host: "",
                port: "",
                database: "",
                username: "",
                password: "",
                confirm_password: "",
                type: "mysql"
            },
            apiConnection: {
                url: "",
                authHeaders: ""
            },
            hasClickedNext: false
        };
    },
    computed: {
        itemIdToEdit() {
            return this.$store.state.moduleManagement.module.id || 0;
        },
        sqlMode() {
            return this.item && this.item.type === "sql";
        }
    },
    methods: {
        async validateForm(scope = null) {
            return this.$validator.validateAll(scope).then(result => result);
        },
        validateStep1() {
            return this.validateForm("step-1");
        },
        onValidateStep(result) {
            this.hasClickedNext = !result;
        },
        editItem() {
            this.$store
                .dispatch("moduleManagement/editItem", this.item)
                .then(() => {})
                .catch(err => {
                    console.error(err);
                });
        }
    },
    created() {
        if (!moduleModuleManagement.isRegistered) {
            this.$store.registerModule(
                "moduleManagement",
                moduleModuleManagement
            );
            moduleModuleManagement.isRegistered = true;
        }

        const moduleId = this.$route.params.id;
        this.$store
            .dispatch("moduleManagement/fetchItem", moduleId)
            .then(res => {
                this.item = res.data.success;
            });
    },
    beforeDestroy() {
        moduleModuleManagement.isRegistered = false;
        this.$store.unregisterModule("moduleManagement");
    }
};
</script>
