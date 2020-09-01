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
        title="Edition d'une société"
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
            <form>
                <div class="vx-row">
                    <div class="vx-col w-full">
                        <vs-input
                            v-validate="'max:255|required'"
                            name="name"
                            class="w-full mb-4 mt-5"
                            placeholder="Nom de la société"
                            v-model="itemLocal.name"
                            :color="!errors.has('name') ? 'success' : 'danger'"
                        />
                        <span
                            class="text-danger text-sm"
                            v-show="errors.has('name')"
                            >{{ errors.first("name") }}</span
                        >
                        <vs-input
                            v-validate="'required|numeric|min:14|max:14'"
                            name="siret"
                            class="w-full mb-4 mt-5"
                            placeholder="Siret"
                            v-model="itemLocal.siret"
                        />
                        <span
                            class="text-danger text-sm"
                            v-show="errors.has('siret')"
                            >{{ errors.first("siret") }}</span
                        >
                        <div class="vx-row mt-4" v-if="isAdmin">
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
                                <div>
                                    <small class="ml-1" for
                                        >Période d'essaie</small
                                    >
                                    <vs-switch v-model="itemLocal.is_trial" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </vs-prompt>
</template>

<script>
import { Validator } from "vee-validate";
import errorMessage from "./errorValidForm";

// register custom messages
Validator.localize("fr", errorMessage);

export default {
    props: {
        itemId: {
            type: Number,
            required: true
        }
    },
    data() {
        return {
            itemLocal: Object.assign(
                {},
                this.$store.getters["companyManagement/getItem"](this.itemId)
            )
        };
    },
    computed: {
        activePrompt: {
            get() {
                return this.itemId && this.itemId > 0 ? true : false;
            },
            set(value) {
                this.$store
                    .dispatch("companyManagement/editItem", {})
                    .then(() => {})
                    .catch(err => {
                        console.error(err);
                    });
            }
        },
        isAdmin() {
            const user = this.$store.state.AppActiveUser;
            if (user.roles && user.roles.length > 0) {
                return user.roles.find(
                    r => r.name === "superAdmin" || r.name === "littleAdmin"
                );
            }

            return false;
        },
        permissions() {
            return this.$store.state.roleManagement.permissions;
        },
        validateForm() {
            return (
                !this.errors.any() &&
                this.itemLocal.name !== "" &&
                this.itemLocal.siret !== ""
            );
        }
    },
    methods: {
        init() {
            this.itemLocal = Object.assign(
                {},
                this.$store.getters["companyManagement/getItem"](this.itemId)
            );
        },
        submitItem() {
            if (this.validateForm) {
                this.$store
                    .dispatch("companyManagement/updateItem", this.itemLocal)
                    .then(() => {
                        this.$vs.notify({
                            title: "Modification d'une société",
                            text: `"${this.itemLocal.name}" modifiée avec succès`,
                            iconPack: "feather",
                            icon: "icon-alert-circle",
                            color: "success"
                        });
                    })
                    .catch(error => {
                        this.$vs.notify({
                            title: "Error",
                            text: error.message,
                            iconPack: "feather",
                            icon: "icon-alert-circle",
                            color: "danger"
                        });
                    })
                    .finally(() => this.$vs.loading.close());
            }
        }
    }
};
</script>
