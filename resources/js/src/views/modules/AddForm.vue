<template>
    <div class="p-3 mb-4 mr-4">
        <vs-button @click="activePrompt = true" class="w-full"
            >Ajouter un module</vs-button
        >
        <vs-prompt
            title="Ajouter un module"
            accept-text="Ajouter"
            cancel-text="Annuler"
            button-cancel="border"
            @cancel="clearFields"
            @accept="addModule"
            @close="clearFields"
            :is-valid="validateForm"
            :active.sync="activePrompt"
        >
            <div>
                <form>
                    <div class="vx-row">
                        <div class="vx-col w-full">
                            <v-select
                                label="name"
                                v-model="item.company"
                                :options="companiesData"
                                class="w-full my-8"
                            >
                                <template #header>
                                    <div class="vs-select--label">
                                        Société
                                    </div>
                                </template>
                            </v-select>
                            <vs-input
                                v-validate="'required|max:255'"
                                label-placeholder="Nom"
                                name="name"
                                class="w-full my-8"
                                v-model="item.name"
                                :success="
                                    item.name.length > 0 && errors.has('name')
                                "
                                :danger="errors.has('name')"
                                :danger-text="errors.first('name')"
                            />
                            <div class="w-full flex flex-row justify-center">
                                <vs-radio
                                    class="mx-3"
                                    v-model="item.type"
                                    vs-value="sql"
                                >
                                    SQL
                                </vs-radio>
                                <vs-radio
                                    class="mx-3"
                                    v-model="item.type"
                                    vs-value="api"
                                >
                                    API
                                </vs-radio>
                            </div>
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
import vSelect from "vue-select";

// register custom messages
Validator.localize("fr", errorMessage);

export default {
    components: {
        vSelect
    },
    data() {
        return {
            activePrompt: false,

            item: {
                name: "",
                company: null,
                type: "sql"
            }
        };
    },
    computed: {
        validateForm() {
            return (
                !this.errors.any() &&
                this.item.name != "" &&
                this.item.company != null
            );
        },
        companiesData() {
            return this.$store.state.companyManagement.companies;
        }
    },
    methods: {
        clearFields() {
            this.item = {
                name: "",
                company: null,
                type: "sql"
            };
        },
        addModule() {
            this.$validator.validateAll().then(result => {
                if (result) {
                    const localItem = {
                        name: this.item.name,
                        company_id: this.item.company.id,
                        type: this.item.type
                    };
                    this.$store
                        .dispatch("moduleManagement/addItem", localItem)
                        .then(response => {
                            this.clearFields();
                            this.$vs.notify({
                                title: "Ajout d'un module",
                                text: `"${response.data.success.name}" ajouté avec succès`,
                                iconPack: "feather",
                                icon: "icon-alert-circle",
                                color: "success"
                            });
                        })
                        .catch(error => {
                            this.$vs.notify({
                                title: "Erreur",
                                text: error.message,
                                iconPack: "feather",
                                icon: "icon-alert-circle",
                                color: "danger"
                            });
                        })
                        .finally(() => this.$vs.loading.close());
                }
            });
        }
    }
};
</script>
