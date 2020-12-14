<template>
    <div id="page-module-view">
        <router-link
            :to="'/modules'"
            class="flex items-center cursor-pointer text-inherit hover:text-primary pt-3 mb-3"
        >
            <feather-icon class="'h-5 w-5" icon="ArrowLeftIcon"></feather-icon>
            <span class="ml-2">Retour à la liste des modules</span>
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
                        autocomplete="off"
                        :title="null"
                        :subtitle="null"
                        @on-complete="$router.push('/modules')"
                        nextButtonText="Suivant"
                        backButtonText="Précédent"
                        finishButtonText="Valider"
                        color="rgba(var(--vs-primary), 1)"
                        errorColor="rgba(var(--vs-danger), 1)"
                    >
                        <!-- Connection configuration -->
                        <tab-content
                            title="Paramètres de connexion"
                            icon="feather icon-database"
                            :before-change="validateFirstStep"
                        >
                            <!-- Sql configuration -->
                            <form autocomplete="off" v-if="item && sqlMode" data-vv-scope="step-1">
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
                                        vs-w="3"
                                        vs-xs="12"
                                        class="px-2"
                                    >
                                        <vs-input
                                            v-validate="'required|max:255'"
                                            name="host"
                                            label-placeholder="Hôte"
                                            v-model="connection.host"
                                            @input="onConnectionInputChange"
                                            class="w-full"
                                            :success="
                                                connection.host.length > 0 &&
                                                    !errors.has(
                                                        'host',
                                                        'step-1'
                                                    )
                                            "
                                            :danger="
                                                errors.has('host', 'step-1')
                                            "
                                            :danger-text="
                                                errors.first('host', 'step-1')
                                            "
                                        />
                                    </vs-col>
                                    <vs-col
                                        vs-type="flex"
                                        vs-justify="center"
                                        vs-align="center"
                                        vs-w="3"
                                        vs-xs="12"
                                        class="px-2"
                                    >
                                        <vs-input
                                            v-validate="'numeric|max:5'"
                                            name="port"
                                            label-placeholder="Port"
                                            v-model="connection.port"
                                            @input="onConnectionInputChange"
                                            class="w-full"
                                            :success="
                                                connection.port.length > 0 &&
                                                    !errors.has(
                                                        'port',
                                                        'step-1'
                                                    )
                                            "
                                            :danger="
                                                errors.has('port', 'step-1')
                                            "
                                            :danger-text="
                                                errors.first('port', 'step-1')
                                            "
                                        />
                                    </vs-col>
                                    <vs-col
                                        vs-type="flex"
                                        vs-justify="center"
                                        vs-align="center"
                                        vs-w="3"
                                        vs-xs="12"
                                        class="px-2"
                                    >
                                        <vs-input
                                            v-validate="'required|max:255'"
                                            name="charset"
                                            label-placeholder="Codage des caractères"
                                            v-model="connection.charset"
                                            @input="onConnectionInputChange"
                                            class="w-full"
                                            :success="
                                                connection.charset.length > 0 &&
                                                    !errors.has(
                                                        'charset',
                                                        'step-1'
                                                    )
                                            "
                                            :danger="
                                                errors.has('charset', 'step-1')
                                            "
                                            :danger-text="
                                                errors.first(
                                                    'charset',
                                                    'step-1'
                                                )
                                            "
                                        />
                                    </vs-col>
                                    <vs-col
                                        vs-type="flex"
                                        vs-justify="center"
                                        vs-align="center"
                                        vs-w="3"
                                        vs-xs="12"
                                        class="px-2"
                                    >
                                        <vs-input
                                            v-validate="'required|max:255'"
                                            name="database"
                                            label-placeholder="Base de données"
                                            v-model="connection.database"
                                            @input="onConnectionInputChange"
                                            class="w-full"
                                            :success="
                                                connection.database.length >
                                                    0 &&
                                                    !errors.has(
                                                        'database',
                                                        'step-1'
                                                    )
                                            "
                                            :danger="
                                                errors.has('database', 'step-1')
                                            "
                                            :danger-text="
                                                errors.first(
                                                    'database',
                                                    'step-1'
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
                                    class="py-3"
                                >
                                    <vs-col
                                        vs-type="flex"
                                        vs-justify="center"
                                        vs-align="center"
                                        vs-w="4"
                                        vs-xs="12"
                                        class="px-2"
                                    >
                                        <vs-input
                                            v-validate="'required|max:255'"
                                            name="username"
                                            label-placeholder="Utilisateur"
                                            v-model="connection.username"
                                            @input="onConnectionInputChange"
                                            class="w-full"
                                            :success="
                                                connection.username.length >
                                                    0 &&
                                                    !errors.has(
                                                        'username',
                                                        'step-1'
                                                    )
                                            "
                                            :danger="
                                                errors.has('username', 'step-1')
                                            "
                                            :danger-text="
                                                errors.first(
                                                    'username',
                                                    'step-1'
                                                )
                                            "
                                        />
                                    </vs-col>
                                    <vs-col
                                        vs-type="flex"
                                        vs-justify="center"
                                        vs-align="center"
                                        vs-w="4"
                                        vs-xs="12"
                                        class="px-2"
                                    >
                                        <vs-input
                                            ref="password"
                                            type="password"
                                            v-validate="
                                                'min:8|max:50' +
                                                    (!connection.has_password
                                                        ? '|required'
                                                        : '')
                                            "
                                            name="password"
                                            label-placeholder="Mot de passe"
                                            v-model="connection.password"
                                            @input="onConnectionInputChange"
                                            class="w-full"
                                            :success="
                                                connection.password.length >
                                                    0 &&
                                                    !errors.has(
                                                        'password',
                                                        'step-1'
                                                    )
                                            "
                                            :danger="
                                                errors.has('password', 'step-1')
                                            "
                                            :danger-text="
                                                errors.first(
                                                    'password',
                                                    'step-1'
                                                )
                                            "
                                        />
                                    </vs-col>
                                    <vs-col
                                        vs-type="flex"
                                        vs-justify="center"
                                        vs-align="center"
                                        vs-w="4"
                                        vs-xs="12"
                                        class="px-2"
                                    >
                                        <vs-input
                                            type="password"
                                            v-validate="
                                                'min:8|max:50|confirmed:password' +
                                                    (!connection.has_password
                                                        ? '|required'
                                                        : '')
                                            "
                                            name="confirm_password"
                                            label-placeholder="Confirmation mot de passe"
                                            v-model="
                                                connection.confirm_password
                                            "
                                            @input="onConnectionInputChange"
                                            class="w-full"
                                            :success="
                                                connection.confirm_password
                                                    .length > 0 &&
                                                    !errors.has(
                                                        'confirm_password',
                                                        'step-1'
                                                    )
                                            "
                                            :danger="
                                                errors.has(
                                                    'confirm_password',
                                                    'step-1'
                                                )
                                            "
                                            :danger-text="
                                                errors.first(
                                                    'confirm_password',
                                                    'step-1'
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
                                >
                                    <vs-col
                                        vs-type="flex"
                                        vs-justify="center"
                                        vs-align="center"
                                        vs-w="2"
                                        vs-xs="6"
                                        class="py-3"
                                    >
                                        <vs-radio
                                            class="mx-3"
                                            v-model="connection.driver"
                                            @change="onConnectionInputChange"
                                            vs-value="mysql"
                                        >
                                            MySQL
                                        </vs-radio>
                                    </vs-col>
                                    <vs-col
                                        vs-type="flex"
                                        vs-justify="center"
                                        vs-align="center"
                                        vs-w="2"
                                        vs-xs="6"
                                        class="py-3"
                                    >
                                        <vs-radio
                                            class="mx-3"
                                            v-model="connection.driver"
                                            @change="onConnectionInputChange"
                                            vs-value="sqlite"
                                        >
                                            SQLite
                                        </vs-radio>
                                    </vs-col>
                                    <vs-col
                                        vs-type="flex"
                                        vs-justify="center"
                                        vs-align="center"
                                        vs-w="2"
                                        vs-xs="6"
                                        class="py-3"
                                    >
                                        <vs-radio
                                            class="mx-3"
                                            v-model="connection.driver"
                                            @change="onConnectionInputChange"
                                            vs-value="pgsql"
                                        >
                                            PostgreSQL
                                        </vs-radio>
                                    </vs-col>
                                    <vs-col
                                        vs-type="flex"
                                        vs-justify="center"
                                        vs-align="center"
                                        vs-w="2"
                                        vs-xs="6"
                                        class="py-3"
                                    >
                                        <vs-radio
                                            class="mx-3"
                                            v-model="connection.driver"
                                            @change="onConnectionInputChange"
                                            vs-value="sqlsrv"
                                        >
                                            SQL Server
                                        </vs-radio>
                                    </vs-col>
                                </vs-row>

                                <vs-divider />

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
                                        <vs-button
                                            v-if="!dbConnection.test_passed"
                                            @click="openConnectionPrompt"
                                        >
                                            Testez la connexion
                                        </vs-button>
                                        <div
                                            v-if="dbConnection.test_passed"
                                            class="flex flex-col items-center justify-center"
                                        >
                                            <div
                                                class="mb-3 p-8 h-8 w-8 flex items-center justify-center bg-success rounded-full"
                                            >
                                                <feather-icon
                                                    :icon="
                                                        `${
                                                            dbConnection.test_passed
                                                                ? 'Check'
                                                                : 'X'
                                                        }Icon`
                                                    "
                                                    svgClasses="h-8 w-8 text-white"
                                                />
                                            </div>
                                            <span>
                                                Test de connexion réussi
                                            </span>
                                        </div>
                                        <vs-prompt
                                            title="Test de connexion"
                                            :color="connectionStatusColor"
                                            :buttons-hidden="true"
                                            :active.sync="
                                                dbConnection.prompt_active
                                            "
                                        >
                                            <div>
                                                <div
                                                    id="connection-div"
                                                    class="vs-con-loading__container h-16"
                                                    v-if="
                                                        dbConnection.is_testing
                                                    "
                                                ></div>
                                                <div
                                                    v-if="
                                                        !dbConnection.is_testing
                                                    "
                                                >
                                                    <vs-row
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
                                                            <div
                                                                :class="[
                                                                    `mb-3 p-8 h-8 w-8 flex items-center justify-center bg-${connectionStatusColor} rounded-full`
                                                                ]"
                                                            >
                                                                <feather-icon
                                                                    :icon="
                                                                        `${
                                                                            dbConnection.test_passed
                                                                                ? 'Check'
                                                                                : 'X'
                                                                        }Icon`
                                                                    "
                                                                    svgClasses="h-8 w-8 text-white"
                                                                />
                                                            </div>
                                                        </vs-col>
                                                    </vs-row>
                                                    <vs-row
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
                                                            <span>
                                                                {{
                                                                    dbConnection.test_passed
                                                                        ? "Connexion réussie !"
                                                                        : "La connexion a échouée, vérifiez les données de connexion et réessayez"
                                                                }}
                                                            </span>
                                                        </vs-col>
                                                    </vs-row>
                                                </div>
                                            </div>
                                        </vs-prompt>
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
                                        <span
                                            v-if="
                                                errors.has('dbTest', 'step-1')
                                            "
                                            class="text-danger"
                                        >
                                            {{
                                                errors.first("dbTest", "step-1")
                                            }}
                                        </span>
                                    </vs-col>
                                </vs-row>
                            </form>

                            <!-- Api configuration -->
                            <form  autocomplete="off"
                                v-if="item && !sqlMode"
                                data-vv-scope="step-1"
                            >
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
                                        vs-xs="12"
                                        class="px-2"
                                    >
                                        <vs-input
                                            v-validate="'required|max:255'"
                                            name="url"
                                            label-placeholder="Url"
                                            v-model="connection.url"
                                            @input="onConnectionInputChange"
                                            class="w-full"
                                            :success="
                                                connection.url.length > 0 &&
                                                    !errors.has('url', 'step-1')
                                            "
                                            :danger="
                                                errors.has('url', 'step-1')
                                            "
                                            :danger-text="
                                                errors.first('url', 'step-1')
                                            "
                                        />
                                    </vs-col>
                                    <vs-col
                                        vs-type="flex"
                                        vs-justify="center"
                                        vs-align="center"
                                        vs-w="6"
                                        vs-xs="12"
                                        class="px-2"
                                    >
                                        <vs-input
                                            v-validate="'max:255'"
                                            name="auth_headers"
                                            label-placeholder="Entêtes d'authentification"
                                            v-model="connection.auth_headers"
                                            @input="onConnectionInputChange"
                                            class="w-full"
                                            :success="
                                                connection.auth_headers.length >
                                                    0 &&
                                                    !errors.has(
                                                        'auth_headers',
                                                        'step-1'
                                                    )
                                            "
                                            :danger="
                                                errors.has(
                                                    'auth_headers',
                                                    'step-1'
                                                )
                                            "
                                            :danger-text="
                                                errors.first(
                                                    'auth_headers',
                                                    'step-1'
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
                            :before-change="dataTypesBeforeChange"
                        >
                            <data-types
                                ref="dataTypes"
                                :module="item"
                            ></data-types>
                        </tab-content>
                    </form-wizard>
                </vx-card>
            </vs-col>
        </vs-row>
    </div>
</template>

<script>
import DataTypes from "./data-types/Index";

import { Validator } from "vee-validate";
import { FormWizard, TabContent } from "vue-form-wizard";
import "vue-form-wizard/dist/vue-form-wizard.min.css";

import errorMessage from "./errorValidForm";
Validator.localize("fr", errorMessage);

import moduleModuleManagement from "@/store/module-management/moduleModuleManagement.js";

export default {
    components: {
        DataTypes,
        FormWizard,
        TabContent
    },
    data() {
        return {
            item: null,
            connection: {
                id: "",
                url: "",
                auth_headers: "",
                driver: "mysql",
                host: "",
                port: "",
                charset: "",
                database: "",
                username: "",
                password: "",
                confirm_password: "",
                has_password: false,
                has_changes: false
            },
            dbConnection: {
                test_passed: false,
                is_testing: false,
                prompt_active: false
            },
            dataTypesBeforeChange: null
        };
    },
    computed: {
        itemIdToEdit() {
            return this.$store.state.moduleManagement.module.id || 0;
        },
        sqlMode() {
            return this.item && this.item.type === "sql";
        },
        connectionStatusColor() {
            return this.dbConnection.is_testing
                ? "primary"
                : this.dbConnection.test_passed
                ? "success"
                : "danger";
        }
    },
    methods: {
        onConnectionInputChange() {
            this.connection.has_changes = true;
            this.dbConnection.test_passed = false;
        },
        validateFirstStep() {
            return new Promise((resolve, reject) => {
                this.$validator.validateAll("step-1").then(result => {
                    if (result) {
                        if (this.sqlMode && !this.dbConnection.test_passed) {
                            this.errors.add({
                                field: "dbTest",
                                scope: "step-1",
                                msg:
                                    "Vous devez tester la connexion avant de poursuivre"
                            });
                            reject("Error");
                        } else {
                            if (this.connection.has_changes) {
                                this.updateModule().then(() => resolve(true));
                            } else {
                                resolve(true);
                            }
                        }
                    } else {
                        reject("Error");
                    }
                });
            });
        },
        openConnectionPrompt() {
            this.dbConnection.test_passed = false;
            this.dbConnection.is_testing = true;
            this.dbConnection.prompt_active = true;
            this.errors.remove("dbTest", "step-1");
            setTimeout(() => {
                this.$vs.loading({
                    container: "#connection-div",
                    type: "point"
                });
                const payload = {
                    id: this.connection.id,
                    driver: this.connection.driver,
                    host: this.connection.host,
                    port: this.connection.port,
                    charset: this.connection.charset,
                    database: this.connection.database,
                    username: this.connection.username,
                    password: this.connection.password
                };

                this.$store
                    .dispatch("moduleManagement/testConnection", payload)
                    .then(response => {
                        this.dbConnection.test_passed = true;
                    })
                    .finally(() => {
                        this.dbConnection.is_testing = false;
                        this.$vs.loading.close(
                            "#connection-div > .con-vs-loading"
                        );
                    });
            }, 1000);
        },
        updateModule() {
            this.$vs.loading();
            const payload = {
                id: this.connection.id,
                type: this.item.type
            };
            if (this.sqlMode) {
                payload.driver = this.connection.driver;
                payload.host = this.connection.host;
                payload.port = this.connection.port;
                payload.charset = this.connection.charset;
                payload.database = this.connection.database;
                payload.username = this.connection.username;
                payload.password = this.connection.password;
                payload.c_password = this.connection.confirm_password;
            } else {
                payload.url = this.connection.url;
                payload.auth_headers = this.connection.auth_headers;
            }
            return this.$store
                .dispatch("moduleManagement/updateModule", payload)
                .then(response => {
                    this.$vs.notify({
                        title: "Succès",
                        text: "Données de connexion misent à jour avec succès",
                        iconPack: "feather",
                        icon: "icon-alert-circle",
                        color: "success"
                    });
                    this.connection.has_changes = false;
                })
                .catch(error =>
                    this.$vs.notify({
                        title: "Erreur",
                        text: error.message,
                        iconPack: "feather",
                        icon: "icon-alert-circle",
                        color: "danger"
                    })
                )
                .finally(() => this.$vs.loading.close());
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
                this.item = res.data.success.base;
                this.connection = {
                    ...this.connection,
                    ...res.data.success.connection,
                    driver: res.data.success.connection.driver || "mysql",
                    has_changes: false
                };
                this.dbConnection.test_passed = this.connection.has_password;
            });
    },
    mounted() {
        setTimeout(() => {
            if (this.$refs.dataTypes) {
                this.dataTypesBeforeChange = this.$refs.dataTypes.beforeChange;
            }
        }, 1000);
    },
    beforeDestroy() {
        moduleModuleManagement.isRegistered = false;
        this.$store.unregisterModule("moduleManagement");
    }
};
</script>
