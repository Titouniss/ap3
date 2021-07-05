<!-- =========================================================================================
  File Name: UserEditTabInformation.vue
  Description: User Edit Information Tab content
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
========================================================================================== -->

<template>
    <div id="user-edit-tab-info">
        <!-- Avatar Row -->
        <div class="vx-row">
            <div class="vx-col w-full">
                <div class="flex items-start flex-col sm:flex-row">
                    <img :src="data.avatar" class="mr-8 rounded h-24 w-24" />
                    <!-- <vs-avatar :src="data.avatar" size="80px" class="mr-4" /> -->
                    <div>
                        <p class="text-lg font-medium mb-2 mt-4 sm:mt-0">
                            {{ data.name }}
                        </p>
                        <input
                            type="file"
                            class="hidden"
                            ref="update_avatar_input"
                            @change="update_avatar"
                            accept="image/*"
                        />

                        <!-- Toggle comment of below buttons as one for actual flow & currently shown is only for demo -->
                        <vs-button class="mr-4 mb-4">Changer Avatar</vs-button>
                        <!-- <vs-button type="border" class="mr-4" @click="$refs.update_avatar_input.click()">Change Avatar</vs-button> -->

                        <vs-button type="border" color="danger"
                            >Retirer Avatar</vs-button
                        >
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Row -->
        <div class="vx-row">
            <div class="vx-col md:w-1/2 w-full">
                <vs-input
                    class="w-full mt-4"
                    label="Prénom"
                    v-model="data_local.firstname"
                    v-validate="'required|alpha_num'"
                    name="firstname"
                />
                <span
                    class="text-danger text-sm"
                    v-show="errors.has('firstname')"
                    >{{ errors.first("firstname") }}</span
                >

                <vs-input
                    class="w-full mt-4"
                    label="Nom"
                    v-model="data_local.lastname"
                    v-validate="'required|alpha_spaces'"
                    name="lastname"
                />
                <span
                    class="text-danger text-sm"
                    v-show="errors.has('lastname')"
                    >{{ errors.first("lastname") }}</span
                >

                <vs-input
                    class="w-full mt-4"
                    label="Email"
                    v-model="data_local.email"
                    type="email"
                    v-validate="'required|email'"
                    name="email"
                />
                <span
                    class="text-danger text-sm"
                    v-show="errors.has('email')"
                    >{{ errors.first("email") }}</span
                >
            </div>

            <div class="vx-col md:w-1/2 w-full">
                <vs-input
                    class="w-full mt-4"
                    label="Rôle"
                    v-model="data_local.role.name"
                    v-validate="'alpha_spaces'"
                    name="role"
                    disabled="true"
                />
                <span class="text-danger text-sm" v-show="errors.has('role')">{{
                    errors.first("role")
                }}</span>

                <vs-input
                    v-if="data_local.company !== null"
                    class="w-full mt-4"
                    label="Société"
                    v-model="data_local.company.name"
                    v-validate="'alpha_spaces'"
                    name="company"
                    disabled="true"
                />
                <vs-input
                    v-else
                    class="w-full mt-4"
                    label="Société"
                    placeholder="Pas de société"
                    v-validate="'alpha_spaces'"
                    name="company"
                    disabled="true"
                />
                <span
                    class="text-danger text-sm"
                    v-show="errors.has('company')"
                    >{{ errors.first("company") }}</span
                >
            </div>
        </div>
        <br />
        <vs-button v-if="isManager" class="ml-auto mt-2" @click="generateLink"
            >Générer le lien d'inscription
        </vs-button>
        <vs-button
            v-if="isManager"
            class="ml-auto mt-2"
            @click="activePrompt = true"
            >Envoyer le lien d'inscription par mail
        </vs-button>

        <!-- Save & Reset Button -->
        <div class="vx-row">
            <div class="vx-col w-full">
                <div class="mt-8 flex flex-wrap items-center justify-end">
                    <vs-button
                        class="ml-auto mt-2"
                        @click="save_changes"
                        :disabled="!validateForm"
                        >Sauvegarder</vs-button
                    >
                    <vs-button
                        class="ml-4 mt-2"
                        type="border"
                        color="warning"
                        @click="reset_data"
                        >Réinitialiser</vs-button
                    >
                </div>
            </div>
        </div>
        <vs-prompt
            title="Envoyer le lien d'inscription par mail "
            accept-text="Envoyer"
            cancel-text="Annuler"
            button-cancel="border"
            @cancel="clearFields"
            @accept="sendMail"
            @close="clearFields"
            :is-valid="validateFormMail"
            :active.sync="activePrompt"
        >
            <div class="form">
                <form autocomplete="off">
                    <span>Adresse Mail</span>
                    <vs-input
                        v-validate="'required|email'"
                        name="email"
                        type="email"
                        label-placeholder="Email"
                        v-model="mail0"
                        class="w-full mb-4"
                    />
                    <span
                        class="text-danger text-sm"
                        v-show="errors.has('email')"
                    >
                        {{ errors.first("email") }}
                    </span>
                    <div v-for="i in nbAddress" :key="i">
                        <span>Adresse Mail </span>
                        <vs-input
                            v-validate="'required|email'"
                            name="email"
                            type="email"
                            label-placeholder="Email"
                            v-model="mails[i]"
                            class="w-full mb-4"
                        />
                        <span
                            class="text-danger text-sm"
                            v-show="errors.has('email')"
                        >
                            {{ errors.first("email") }}
                        </span>
                    </div>
                    <vs-button class="ml-auto mt-2" @click="addAddress"
                        >Ajouter une adresse mail
                    </vs-button>
                </form>
            </div>
        </vs-prompt>
    </div>
</template>

<script>
import vSelect from "vue-select";

export default {
    components: {
        vSelect
    },
    props: {
        data: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            activePrompt: false,
            nbAddress: 0,
            mail0: "",
            mails: [],
            data_local: JSON.parse(JSON.stringify(this.data)),

            statusOptions: [
                { label: "Active", value: "active" },
                { label: "Blocked", value: "blocked" },
                { label: "Deactivated", value: "deactivated" }
            ],
            roleOptions: [
                { label: "Admin", value: "admin" },
                { label: "User", value: "user" },
                { label: "Staff", value: "staff" }
            ]
        };
    },
    computed: {
        isManager() {
            return this.$store.state.AppActiveUser.is_manager;
        },
        status_local: {
            get() {
                return {
                    label: this.capitalize(this.data_local.status),
                    value: this.data_local.status
                };
            },
            set(obj) {
                this.data_local.status = obj.value;
            }
        },
        role_local: {
            get() {
                return {
                    label: this.capitalize(this.data_local.role),
                    value: this.data_local.role
                };
            },
            set(obj) {
                this.data_local.role = obj.value;
            }
        },
        validateForm() {
            return !this.errors.any();
        },
        validateFormMail() {
            return !this.errors.any() && this.mail0 != null && this.mail0 != "";
        }
    },
    methods: {
        capitalize(str) {
            if (!str) return "";
            return str.slice(0, 1).toUpperCase() + str.slice(1, str.length);
        },
        save_changes() {
            /* eslint-disable */
            if (!this.validateForm) return;

            // Here will go your API call for updating data
            // You can get data in "this.data_local"

            this.$store
                .dispatch("userManagement/updateAccount", this.data_local)
                .then(() => {
                    this.$vs.loading.close();
                    this.$vs.notify({
                        title: "Modification du compte",
                        text: "Vos données ont étés modifiées avec succès",
                        iconPack: "feather",
                        icon: "icon-alert-circle",
                        color: "success"
                    });
                })
                .catch(error => {
                    this.$vs.loading.close();
                    this.$vs.notify({
                        title: "Error",
                        text:
                            "Une erreur est survenue, veuillez réessayer plus tard.",
                        iconPack: "feather",
                        icon: "icon-alert-circle",
                        color: "danger"
                    });
                });

            /* eslint-enable */
        },
        reset_data() {
            this.data_local = JSON.parse(JSON.stringify(this.data));
        },
        update_avatar() {
            // You can update avatar Here
            // For reference you can check dataList example
            // We haven't integrated it here, because data isn't saved in DB
        },
        generateLink() {
            this.$vs.dialog({
                color: "primary",
                title: "Lien d'inscription",
                text:
                    "app.plannigo.fr/pages/register?company=" +
                    this.data_local.company.name,
                acceptText: "OK"
            });
        },
        clearFields() {
            this.activePrompt = false;
        },
        addAddress() {
            this.nbAddress++;
        },
        sendMail() {
            this.mails[0] = this.mail0;
            this.$store
                .dispatch("auth/registrationLink", {
                    id: this.$route.params.userId,
                    email: this.mails
                })
                .catch(error => {
                    this.$vs.notify({
                        title: "Echec",
                        text: error.message,
                        iconPack: "feather",
                        icon: "icon-alert-circle",
                        color: "danger"
                    });
                });
        }
    }
};
</script>

<style>
.form {
    max-height: 450px;
    overflow-y: auto;
    overflow-x: hidden;
}
</style>
