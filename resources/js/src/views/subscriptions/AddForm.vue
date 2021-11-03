<template>
    <div class="p-3 mb-4 mr-4">
        <vs-button @click="activePrompt = true" class="w-full">
            Ajouter un abonnement
        </vs-button>
        <vs-prompt
            title="Ajouter un abonnement"
            accept-text="Ajouter"
            cancel-text="Annuler"
            button-cancel="border"
            @cancel="clearFields"
            @accept="addSubscription"
            @close="clearFields"
            :is-valid="validateForm"
            :active.sync="activePrompt"
        >
            <vs-row
                vs-justify="flex-start"
                vs-type="flex"
                vs-w="12"
                class="px-3"
            >
                <vs-col vs-w="12" class="pb-3">
                    <infinite-select
                        required
                        header="Modules"
                        model="package"
                        label="display_name"
                        multiple
                        v-model="itemLocal.packages"
                        :autocomplete="false"
                    />
                </vs-col>
                <vs-col vs-w="12" class="pb-3">
                    <small class="ml-2"> Date de début </small>
                    <flat-pickr
                        name="starts_at"
                        v-validate="'required'"
                        :config="configDatePicker(null, itemLocal.ends_at)"
                        class="w-full"
                        v-model="itemLocal.starts_at"
                    />
                    <small v-show="errors.has('starts_at')" class="text-danger">
                        {{ errors.first("starts_at") }}
                    </small>
                </vs-col>
                <vs-col vs-w="12" vs-xs="12" class="pb-3">
                    <small class="ml-2"> Date de fin </small>
                    <flat-pickr
                        name="ends_at"
                        v-validate="'required'"
                        :config="configDatePicker(itemLocal.starts_at)"
                        v-model="itemLocal.ends_at"
                        class="w-full"
                    />
                    <small v-show="errors.has('ends_at')" class="text-danger">
                        {{ errors.first("ends_at") }}
                    </small>
                </vs-col>
                <vs-col vs-w="12" vs-xs="12" class="pb-3">
                    <small class="ml-2"> Période d'essai </small>
                    <vs-switch
                        vs-icon-off="close"
                        vs-icon-on="done"
                        v-model="itemLocal.is_trial"
                        name="is_trial"
                    >
                    </vs-switch>
                </vs-col>
            </vs-row>
        </vs-prompt>
    </div>
</template>

<script>
import { Validator } from "vee-validate";
import errorMessage from "./errorValidForm";
import InfiniteSelect from "@/components/inputs/selects/InfiniteSelect";

// FlatPickr import
import flatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import { French as FrenchLocale } from "flatpickr/dist/l10n/fr.js";

import moment from "moment";

// register custom messages
Validator.localize("fr", errorMessage);

export default {
    components: {
        InfiniteSelect,
        flatPickr
    },
    props: {
        companyId: {
            type: Number,
            required: true
        }
    },
    data() {
        return {
            activePrompt: false,

            itemLocal: {
                starts_at: null,
                ends_at: null,
                packages: [],
                is_trial: false
            },
            configDatePicker: (minDate = null, maxDate = null) => ({
                altInput: true,
                altFormat: "d/m/Y",
                disableMobile: "true",
                locale: FrenchLocale,
                minDate,
                maxDate
            })
        };
    },
    computed: {
        validateForm() {
            return (
                !this.errors.any() &&
                this.companyId &&
                this.itemLocal.starts_at &&
                this.itemLocal.ends_at &&
                this.itemLocal.packages &&
                this.itemLocal.packages.length > 0
            );
        }
    },
    methods: {
        clearFields() {
            Object.assign(this.itemLocal, {
                starts_at: null,
                ends_at: null,
                packages: [],
                is_trial: false
            });
            this.activePrompt = false;
        },
        addSubscription() {
            this.$validator.validateAll().then(result => {
                if (result) {
                    const item = JSON.parse(JSON.stringify(this.itemLocal));
                    item.company_id = this.companyId;
                    this.$store
                        .dispatch("subscriptionManagement/addItem", item)
                        .then(() => {
                            this.clearFields();
                            this.$vs.notify({
                                title: "Ajout d'un abonnement",
                                text: `Abonnement du ${moment(
                                    item.starts_at
                                ).format("DD/MM/YYYY")} au ${moment(
                                    item.ends_at
                                ).format("DD/MM/YYYY")} ajouté avec succès`,
                                iconPack: "feather",
                                icon: "icon-alert-circle",
                                color: "success"
                            });
                        })
                        .catch(error => {
                            this.activePrompt = true;
                            this.$vs.notify({
                                title: "Error",
                                text: error.message,
                                iconPack: "feather",
                                icon: "icon-alert-circle",
                                color: "danger"
                            });
                        })
                        .finally(() => {
                            this.$vs.loading.close();
                        });
                }
            });
        }
    }
};
</script>
