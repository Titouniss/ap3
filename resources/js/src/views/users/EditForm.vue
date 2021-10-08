<!-- =========================================================================================
  File Name: UserEdit.vue
  Description: user Edit Page
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/role/pixinvent
========================================================================================== -->

<template>
    <div>
        <vx-card>
            <vs-row vs-justify="center" vs-type="flex" vs-w="12">
                <vs-col vs-w="6" vs-xs="12" class="mt-6 px-6">
                    <!-- Lastname -->
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
                    >
                        {{ errors.first("lastname") }}
                    </span>

                    <!-- Firstname -->
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
                    >
                        {{ errors.first("firstname") }}
                    </span>

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

                    <!-- Email -->
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
                        {{ errors.first("email") }} </span
                    ><br />

                    <vs-button @click="sendVerificationEmail">
                        Renvoyer un mail de vérification
                    </vs-button>
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

                    <div v-if="itemLocal.company_id">
                        <infinite-select
                            required
                            header="Rôle"
                            label="name"
                            model="role"
                            v-model="itemLocal.role_id"
                            :filters="rolesFilter"
                            :item-fields="['name', 'company_id', 'is_public']"
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
                                regex: /^-?([0-9]{1,3})(?:.[0-9]{1,2})?$/
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
                    <!-- Related users -->
                    <div
                        v-if="
                            itemLocal.company_id === initial_company_id &&
                                itemLocal.related_users &&
                                itemLocal.related_users.length !== 0
                        "
                        class="mt-5 p-3 border border-solid rounded"
                        style="border-color: rgba(var(--vs-warning), 1)"
                    >
                        <v-select
                            name="user"
                            v-model="itemLocal.related_user_id"
                            :reduce="user => user.id"
                            class="w-full"
                            autocomplete
                            :options="itemLocal.related_users"
                        >
                            <template #header>
                                <div style="opacity: .8 font-size: .85rem">
                                    Utilisateur à relier
                                </div>
                            </template>
                            <template #option="user">
                                <span>
                                    {{
                                        `${user.firstname} ${user.lastname} (${user.login})`
                                    }}
                                </span>
                            </template>
                            <template #selected-option="user">
                                <span>
                                    {{
                                        `${user.firstname} ${user.lastname} (${user.login})`
                                    }}
                                </span>
                            </template>
                        </v-select>
                    </div>
                </vs-col>
            </vs-row>

            <!-- Save & Reset Button -->
            <div class="vx-row">
                <div class="vx-col w-full">
                    <div class="mt-8 flex flex-wrap items-center justify-end">
                        <vs-button
                            class="ml-auto mt-2"
                            @click="confirmUpdateItem"
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

        <work-hours :data="itemLocal" />
    </div>
</template>

<script>
import InfiniteSelect from "@/components/inputs/selects/InfiniteSelect";
import WorkHours from "./WorkHours";
import { Validator } from "vee-validate";
import errorMessage from "./errorValidForm";

// register custom messages
Validator.localize("fr", errorMessage);

// Store Module
import moduleUserManagement from "@/store/user-management/moduleUserManagement.js";
import moduleRoleManagement from "@/store/role-management/moduleRoleManagement.js";
import moduleCompanyManagement from "@/store/company-management/moduleCompanyManagement.js";
import moduleSkillManagement from "@/store/skill-management/moduleSkillManagement.js";

var model = "user";
var modelPlurial = "users";

export default {
    components: {
        InfiniteSelect,
        WorkHours
    },
    data() {
        return {
            itemLocal: {
                firstname: "",
                lastname: "",
                login: "",
                full_login: "",
                email: "",
                company_id: 0,
                role_id: 0,
                skills: [],
                hours: 0,
                work_hours: [],
                related_user_id: null
            },
            initial_company_id: null,
            company_id_temps: null,
            company_login: "",
            selected: []
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
        relatedUsers() {
            return this.itemLocal.related_users;
        },
        skillsData() {
            return this.$store.getters["skillManagement/getItems"];
        },
        disabled() {
            if (this.isAdmin) {
                return false;
            } else {
                this.itemLocal.company_id = this.$store.state.AppActiveUser.company_id;
                return true;
            }
        },
        validateForm() {
            return (
                !this.errors.any() &&
                this.itemLocal.name != "" &&
                this.itemLocal.firstname != "" &&
                this.itemLocal.login != "" &&
                this.itemLocal.company_id != null &&
                this.itemLocal.role_id > 0 &&
                this.itemLocal.hours != null
            );
        }
    },
    methods: {
        onCompanyChange() {
            this.getCompanyName();
            this.itemLocal.skills = [];
            const role = this.$store.getters["roleManagement/getItem"](
                this.itemLocal.role_id
            );
            console.log(role);
            if (
                role &&
                role.company_id !== this.itemLocal.company_id &&
                !role.is_public
            ) {
                this.itemLocal.role_id = null;
            }
        },
        authorizedTo(action, model = modelPlurial) {
            return this.$store.getters.userHasPermissionTo(
                `${action} ${model}`
            );
        },
        fetch_data(id) {
            this.$vs.loading();

            this.$store
                .dispatch("userManagement/fetchItem", id)
                .then(data => {
                    let item = data.payload;
                    // Get skills
                    let skill_ids = [];
                    if (item.skills.length > 0) {
                        item.skills.forEach(element => {
                            skill_ids.push(element.id);
                        });
                    }

                    this.itemLocal = {
                        firstname: item.firstname,
                        lastname: item.lastname,
                        login: item.login,
                        email: item.email,
                        company_id: item.company_id,
                        role_id: item.role.id,
                        skills: skill_ids,
                        hours: item.hours,
                        work_hours: item.work_hours
                    };
                    if (item.company_id) {
                        this.initial_company_id = item.company_id;
                        this.company_id_temps = item.company_id;
                    }

                    // Get login
                    if (this.itemLocal.login != null) {
                        this.itemLocal.login = this.itemLocal.login
                            .split(".")
                            .slice(1);
                    } else {
                        this.itemLocal.login = "";
                    }
                    this.getCompanyName();
                })
                .catch(err => {
                    console.error(err);
                })
                .finally(() => this.$vs.loading.close());
        },
        confirmUpdateItem() {
            if (this.itemLocal.related_user_id) {
                this.$vs.dialog({
                    type: "confirm",
                    color: "warning",
                    title: "Confirmation de modifications",
                    text:
                        "Êtes vous sûr de vouloir lier cet utilisateur avec un qui est synchronisé ? Ce dernier sera supprimé définitivement",
                    accept: this.updateItem,
                    acceptText: "Confirmer",
                    cancelText: "Annuler"
                });
            } else {
                this.updateItem();
            }
        },
        updateItem() {
            this.$vs.loading();
            const payload = JSON.parse(JSON.stringify(this.itemLocal));

            // Parse login
            payload.login = "".concat(this.company_login, this.itemLocal.login);

            // Filter skills
            payload.skills = this.companySkills
                .filter(skill => payload.skills.indexOf(skill.id) > -1)
                .map(skill => skill.id);

            this.$store
                .dispatch("userManagement/updateItem", payload)
                .then(() => {
                    this.$vs.loading.close();
                    this.back();
                    this.$vs.notify({
                        title: "Modification",
                        text: "Utilisateur modifier avec succès",
                        iconPack: "feather",
                        icon: "icon-alert-circle",
                        color: "success"
                    });
                })
                .catch(error => {
                    this.$vs.loading.close();
                    this.$vs.notify({
                        title: "Echec",
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
        },
        sendVerificationEmail() {
            // Loading
            this.$vs.loading();
            this.$store
                .dispatch("auth/verify", { email: this.itemLocal.email })
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

        this.fetch_data(parseInt(this.$route.params.userId, 10));
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
