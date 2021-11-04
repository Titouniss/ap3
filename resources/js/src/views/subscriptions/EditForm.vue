<template>
    <vs-prompt
        title="Modifier un abonnement"
        accept-text="Modifier"
        cancel-text="Annuler"
        button-cancel="border"
        @cancel="init"
        @accept="submitItem"
        @close="init"
        :is-valid="validateForm"
        :active.sync="activePrompt"
    >
        <vs-row vs-justify="flex-start" vs-type="flex" vs-w="12" class="px-3">
            <vs-col vs-w="12" class="pb-3">
                <infinite-select
                    required
                    header="Modules"
                    label="display_name"
                    multiple
                    model="package"
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
            <vs-col vs-w="12" class="pb-3">
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
            <vs-col vs-w="6" class="pb-3">
                <small class="ml-2"> Période d'essai </small>
                <vs-switch
                    vs-icon-off="close"
                    vs-icon-on="done"
                    v-model="itemLocal.is_trial"
                    name="is_trial"
                >
                </vs-switch>
            </vs-col>
            <vs-col vs-w="6" class="pb-3">
                <small class="ml-2"> Annulé </small>
                <vs-switch
                    color="danger"
                    vs-icon-off="close"
                    vs-icon-on="done"
                    v-model="itemLocal.is_cancelled"
                ></vs-switch>
            </vs-col>
        </vs-row>
    </vs-prompt>
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
        itemId: {
            type: Number,
            required: true
        }
    },
    data() {
        return {
            itemLocal: {},

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
                this.itemLocal.starts_at &&
                this.itemLocal.ends_at &&
                this.itemLocal.packages &&
                this.itemLocal.packages.length > 0
            );
        },
        activePrompt: {
            get() {
                return this.itemId && this.itemId > 0 ? true : false;
            },
            set(value) {
                this.$store
                    .dispatch("subscriptionManagement/editItem", value || {})
                    .catch(err => {
                        console.error(err);
                    });
            }
        }
    },
    methods: {
        init() {
            const item = this.$store.getters[
                "subscriptionManagement/getSelectedItem"
            ];

            item.packages = item.packages ? item.packages.map(p => p.id) : [];
            item.is_cancelled = item.state === "cancelled";
            this.itemLocal = item;
        },
        submitItem() {
            this.$validator.validateAll().then(result => {
                if (result) {
                    const item = JSON.parse(JSON.stringify(this.itemLocal));
                    item.starts_at = item.starts_at.split(" ").shift();
                    item.ends_at = item.ends_at.split(" ").shift();
                    this.$store
                        .dispatch("subscriptionManagement/updateItem", item)
                        .then(() => {
                            this.$vs.notify({
                                title: "Modification d'un abonnement",
                                text: `Abonnement du ${moment(
                                    item.starts_at
                                ).format("DD/MM/YYYY")} au ${moment(
                                    item.ends_at
                                ).format("DD/MM/YYYY")} modifié avec succès`,
                                iconPack: "feather",
                                icon: "icon-alert-circle",
                                color: "success"
                            });
                        })
                        .catch(error => {
                            this.activePrompt = this.itemLocal;
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
    },
    created() {
        this.init();
    }
};
</script>
