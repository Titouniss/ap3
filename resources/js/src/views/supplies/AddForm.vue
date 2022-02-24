<template>
    <div class="p-3 mb-4 mr-4">
        <vs-button @click="activePrompt = true" class="w-full">
            Ajouter un approvisionnement
        </vs-button>
        <vs-prompt
            title="Ajouter un approvisionnement"
            accept-text="Ajouter"
            cancel-text="Annuler"
            button-cancel="border"
            @cancel="clearFields"
            @accept="addSkill"
            @close="clearFields"
            :is-valid="validateForm"
            :active.sync="activePrompt"
        >
            <div>
                <form autocomplete="off">
                    <div class="vx-row">
                        <div class="vx-col w-full">
                            <vs-input
                                v-validate="'required|max:255'"
                                name="name"
                                class="w-full mb-4 mt-5"
                                placeholder="Nom"
                                v-model="itemLocal.name"
                                :success="
                                    itemLocal.name.length > 0 &&
                                        !errors.first('name')
                                "
                                :danger="errors.has('name')"
                                :danger-text="errors.first('name')"
                            />
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
                                    <div>
                                        <infinite-select
                                            class="mt-5"
                                            required
                                            header="Société"
                                            label="name"
                                            model="company"
                                            v-model="itemLocal.company_id"
                                        />
                                    </div>
                                </div>
                            </div>
                            <!-- <vs-input v-validate="'required'" name="company_id" class="w-full mb-4 mt-5" placeholder="Société" v-model="itemLocal.company_id" :color="validateForm ? 'success' : 'danger'" /> -->
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
import InfiniteSelect from "@/components/inputs/selects/InfiniteSelect";

// register custom messages
Validator.localize("fr", errorMessage);

export default {
    components: {
        InfiniteSelect
    },
    data() {
        return {
            activePrompt: false,

            itemLocal: {
                name: "",
                company_id: null
            }
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
            if (this.isAdmin) {
                return false;
            } else {
                this.itemLocal.company_id = this.$store.state.AppActiveUser.company_id;
                return true;
            }
        }
    },
    methods: {
        clearFields() {
            this.itemLocal = {
                name: "",
                company_id: null
            };
        },
        addSkill() {
            this.$validator.validateAll().then(result => {
                if (result) {
                    this.$store
                        .dispatch(
                            "supplyManagement/addItem",
                            Object.assign({}, this.itemLocal)
                        )
                        .then(() => {
                            this.$vs.notify({
                                title: "Ajout d'un approvisionnement",
                                text: `"${this.itemLocal.name}" ajoutée avec succès`,
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
                        .finally(() => {
                            this.clearFields();
                            this.$vs.loading.close();
                        });
                }
            });
        }
    }
};
</script>
