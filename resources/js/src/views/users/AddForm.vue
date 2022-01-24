<!-- =========================================================================================
  File Name: UserAdd.vue
  Description: user Add Page
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/role/pixinvent
========================================================================================== -->

<template>
    <div id="page-role-add">
        <vx-card>
            <vs-row vs-justify="center" vs-type="flex" vs-w="12">
                <vs-col vs-w="6" vs-xs="12" class="mt-6 px-6">
                    <vs-input
                        v-validate="{
                            required: true,
                            max: 255,
                            regex: /^[^\d.]+$/
                        }"
                        name="lastname"
                        class="w-full mb-4"
                        label="Nom"
                        v-model="itemLocal.lastname"
                        :color="!errors.has('lastname') ? 'success' : 'danger'"
                    />
                    <span
                        class="text-danger text-sm"
                        v-show="errors.has('lastname')"
                        >{{ errors.first("lastname") }}</span
                    >

                    <vs-input
                        v-validate="{
                            required: true,
                            max: 255,
                            regex: /^[^\d.]+$/
                        }"
                        name="firstname"
                        class="w-full mb-4 mt-5"
                        label="Prénom"
                        v-model="itemLocal.firstname"
                        :color="!errors.has('firstname') ? 'success' : 'danger'"
                    />
                    <span
                        class="text-danger text-sm"
                        v-show="errors.has('firstname')"
                        >{{ errors.first("firstname") }}</span
                    >

                    <!-- Login -->
                    <div class="vs-input--label">Identifiant</div>
                    <vx-input-group>
                        <template slot="prepend">
                            <div
                                v-if="company_login"
                                class="prepend-text bg-primary"
                            >
                                <span> {{ company_login }} </span>
                            </div>
                        </template>

                        <vs-input
                            v-validate="{
                                required: true,
                                regex: /^((?![ÇçèéêëÈÉÊËàáâãäå@ÀÁÂÃÄÅìíîïÌÍÎÏðòóôõöÒÓÔÕÖùúûüÙÚÛÜýÿÝ]+).)*$/
                            }"
                            name="login"
                            class="w-full"
                            v-model="itemLocal.login"
                        />
                    </vx-input-group>
                    <span
                        class="text-danger text-sm"
                        v-show="errors.has('login')"
                    >
                        {{ errors.first("login") }}
                    </span>

                    <vs-input
                        v-validate="'email'"
                        name="email"
                        class="w-full mb-4 mt-5"
                        label="E-mail"
                        v-model="itemLocal.email"
                        :color="!errors.has('email') ? 'success' : 'danger'"
                    />
                    <span
                        class="text-danger text-sm"
                        v-show="errors.has('email')"
                    >
                        {{ errors.first("email") }}
                    </span>
                </vs-col>
                <vs-col vs-w="6" vs-xs="12" class="mt-6 px-6">
                    <infinite-select
                        v-if="!disabled"
                        required
                        class="mb-5"
                        header="Société"
                        label="name"
                        model="company"
                        v-model="itemLocal.company_id"
                        @input="onCompanyChange"
                    />

                    <vs-input
                        v-validate="{
                            required: false,
                            max: 255,
                            regex: /^[^\d.]+$/
                        }"
                        name="function"
                        class="w-full mb-4 mt-5"
                        label="Fonction"
                        v-model="itemLocal.function"
                        :color="!errors.has('function') ? 'success' : 'danger'"
                    />
                    <span
                        class="text-danger text-sm"
                        v-show="errors.has('function')"
                        >{{ errors.first("function") }}</span
                    >

                    <div v-if="itemLocal.company_id">
                        <infinite-select
                            required
                            header="Rôle"
                            label="name"
                            model="role"
                            v-model="itemLocal.role_id"
                            :filters="rolesFilter"
                        />

                        <div v-if="skillsData.length === 0" class="mt-12 mb-2">
                            <span label="Compétences" class="msgTxt mt-10">
                                Aucune compétences trouvées.
                            </span>
                            <router-link
                                class="linkTxt"
                                :to="{ path: '/skills' }"
                            >
                                Ajouter une compétence
                            </router-link>
                        </div>
                        <infinite-select
                            v-show="skillsData.length > 0"
                            class="mt-5"
                            required
                            header="Compétences"
                            label="name"
                            model="skill"
                            multiple
                            v-model="itemLocal.skills"
                            :filters="skillsFilter"
                        />
                    </div>

                    <div>
                        <vs-input
                            v-validate="{
                                required: true,
                                regex: /^-?[0-9]{1,3}(?:.[0-9]{1,2})?$/
                            }"
                            name="hours"
                            class="w-full mt-5"
                            label="Nombre d'heures supplémentaires"
                            v-model="itemLocal.hours"
                            :color="!errors.has('hours') ? 'success' : 'danger'"
                        />
                        <span
                            class="text-danger text-sm"
                            v-show="errors.has('hours')"
                        >
                            {{ errors.first("hours") }}
                        </span>
                    </div>
                    <div  class="mt-5"
                       >  <small
                                    class="date-label mb-1 pl-2"
                                    style="display: block"
                                >
                                    Date d'embauche</small>
                      <flat-pickr
                       v-validate="{
                                required: true
                            }"
                                header="Date d'embauche"
                                label="Date d'embauche"
                                name="start_employment"
                                class="w-full"
                                v-model="itemLocal.start_employment"
                                :config="configdateTimePicker"
                            />
                    </div>
                </vs-col>
            </vs-row>

            <!-- Save & Reset Button -->
            <div class="vx-row">
                <div class="vx-col w-full">
                    <div class="mt-8 flex flex-wrap items-center justify-end">
                        <vs-button
                            class="ml-auto mt-2"
                            @click="addItem"
                            :disabled="!validateForm"
                        >
                            Ajouter
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
import InfiniteSelect from "@/components/inputs/selects/InfiniteSelect";
import { Validator } from "vee-validate";
import errorMessage from "./errorValidForm";
import flatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import { French as FrenchLocale } from "flatpickr/dist/l10n/fr.js";

// Store Module
import moduleUserManagement from "@/store/user-management/moduleUserManagement.js";
import moduleRoleManagement from "@/store/role-management/moduleRoleManagement.js";
import moduleCompanyManagement from "@/store/company-management/moduleCompanyManagement.js";
import moduleSkillManagement from "@/store/skill-management/moduleSkillManagement.js";

// register custom messages
Validator.localize("fr", errorMessage);

var model = "user";
var modelPlurial = "users";

export default {
    components: {
        InfiniteSelect,
        flatPickr
    },
    data() {
        return {
            activePrompt: false,
            itemLocal: {
                firstname: "",
                lastname: "",
                login: "",
                full_login: "",
                email: "",
                company_id: 0,
                function: "",
                role_id: 0,
                skills: [],
                hours: 0,
                start_employment : null
            },
            company_login: "",
            configdateTimePicker: {
                disableMobile: "true",
                enableTime: true,
                locale: FrenchLocale,
                minDate: null,
                maxDate: null,
                defaultHour: 9
            },
        };
    },
    computed: {
        skillsFilter() {
            return { company_id: this.itemLocal.company_id };
        },
        rolesFilter() {
            return { company_id: this.itemLocal.company_id };
        },
        isAdmin() {
            return this.$store.state.AppActiveUser.is_admin;
        },
        skillsData() {
            return this.$store.getters["skillManagement/getItems"];
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
        validateForm() {
            return (
                !this.errors.any() &&
                this.itemLocal.lastname != "" &&
                this.itemLocal.firstname != "" &&
                this.itemLocal.login != "" &&
                this.itemLocal.company_id > 0 &&
                this.itemLocal.role_id > 0 &&
                this.itemLocal.hours != null &&
                this.itemLocal.start_employment != null
            );
        }
    },
    methods: {
        onCompanyChange() {
            this.getCompanyName();
            this.itemLocal.skills = [];
            if (
                !this.$store.getters["roleManagement/getItems"].find(
                    r => r.id === this.itemLocal.role_id
                )
            ) {
                this.itemLocal.role_id = 0;
            }
        },
        authorizedTo(action, model = modelPlurial) {
            return this.$store.getters.userHasPermissionTo(
                `${action} ${model}`
            );
        },
        clearFields() {
            Object.assign(this.itemLocal, {
                itemLocal: {
                    email: "",
                    company_id: 0,
                    role_id: 0
                }
            });
        },
        addItem() {
            if (this.validateForm) {
                this.$vs.loading();
                const payload = JSON.parse(JSON.stringify(this.itemLocal));

                // Parse login
                payload.login = "".concat(
                    this.company_login,
                    this.itemLocal.login
                );

                // Filter skills
                payload.skills = this.skillsData
                    .filter(skill => payload.skills.indexOf(skill.id) > -1)
                    .map(skill => skill.id);

                this.$store
                    .dispatch("userManagement/addItem", payload)
                    .then(data => {
                        this.$router
                            .push(
                                `/${modelPlurial}/user-edit/${data.payload.id}`
                            )
                            .catch(() => {});
                        this.$vs.notify({
                            title: "Ajout d'un utilisateur",
                            text: `Utilisateur ajouté avec succès`,
                            iconPack: "feather",
                            icon: "icon-alert-circle",
                            color: "success"
                        });
                        if (payload.email == null || payload.email == "") {
                            this.$vs.dialog({
                                color: "warning",
                                title: "Attention !",
                                text:
                                    "le mot de passe du compte est " +
                                    data.payload.clear_password +
                                    " notez le bien, il devra être changé lors de la première connexion",
                                acceptText: "OK"
                            });
                        }
                    })
                    .catch(error => {
                        this.$vs.notify({
                            title: "Echec",
                            text: error.message,
                            iconPack: "feather",
                            icon: "icon-alert-circle",
                            color: "danger"
                        });
                    })
                    .finally(() => this.$vs.loading.close());
            }
        },
        back() {
            this.$router.push(`/${modelPlurial}`).catch(() => {});
        },
        getCompanyName() {
            if (this.isAdmin) {
                if (this.itemLocal.company_id != null) {
                    let company = this.$store.getters[
                        "companyManagement/getItem"
                    ](this.itemLocal.company_id);

                    this.company_login = "".concat(
                        company.name.replace(/ /gi, "_").toLowerCase(),
                        "."
                    );
                    this.company_login = this.removeAccents(this.company_login);
                } else {
                    this.company_login = "";
                }
            } else {
                const user = this.$store.state.AppActiveUser;
                this.company_login = "".concat(
                    user.company.name.replace(/ /gi, "_").toLowerCase(),
                    "."
                );
                this.company_login = this.removeAccents(this.company_login);
            }
        },
        removeAccents(str) {
            let accents =
                "ÀÁÂÃÄÅàáâãäåßÒÓÔÕÕÖØòóôõöøÈÉÊËèéêëðÇçÐÌÍÎÏìíîïÙÚÛÜùúûüÑñŠšŸÿýŽž";
            let accentsOut =
                "AAAAAAaaaaaaBOOOOOOOooooooEEEEeeeeeCcDIIIIiiiiUUUUuuuuNnSsYyyZz";
            str = str.split("");
            str.forEach((letter, index) => {
                let i = accents.indexOf(letter);
                if (i != -1) {
                    str[index] = accentsOut[i];
                }
            });
            return str.join("");
        }
    },
    created() {
        if (!moduleUserManagement.isRegistered) {
            this.$store.registerModule("userManagement", moduleUserManagement);
            moduleUserManagement.isRegistered = true;
        }
        if (!moduleRoleManagement.isRegistered) {
            this.$store.registerModule("roleManagement", moduleRoleManagement);
            moduleRoleManagement.isRegistered = true;
        }
        if (!moduleCompanyManagement.isRegistered) {
            this.$store.registerModule(
                "companyManagement",
                moduleCompanyManagement
            );
            moduleCompanyManagement.isRegistered = true;
        }
        if (!moduleSkillManagement.isRegistered) {
            this.$store.registerModule(
                "skillManagement",
                moduleSkillManagement
            );
            moduleSkillManagement.isRegistered = true;
        }

        this.getCompanyName();
    },
    beforeDestroy() {
        moduleUserManagement.isRegistered = false;
        moduleRoleManagement.isRegistered = false;
        moduleCompanyManagement.isRegistered = false;
        moduleSkillManagement.isRegistered = false;

        this.$store.unregisterModule("skillManagement");
        this.$store.unregisterModule("userManagement");
        this.$store.unregisterModule("roleManagement");
        this.$store.unregisterModule("companyManagement");
    }
};
</script>
