<!-- =========================================================================================
  File Name: CompanyEdit.vue
  Description: company Edit Page
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/role/pixinvent
========================================================================================== -->
<template>
    <div class="w-full">
        <vx-card class="py-3 px-6">
            <company-details :itemLocal="itemLocal" :showAll="false" />
            <div class="pt-6 px-3 flex items-end">
                <feather-icon
                    svgClasses="w-6 h-6"
                    icon="FilePlusIcon"
                    class="mr-2"
                />
                <span class="font-medium text-lg leading-none">
                    Abonnement
                </span>
            </div>
            <vs-divider />
            <vs-row vs-justify="flex-start" vs-type="flex" vs-w="12">
                <vs-col vs-w="4" vs-xs="12" class="px-3">
                    <infinite-select
                        required
                        header="Modules"
                        label="display_name"
                        multiple
                        model="package"
                        v-model="subscription.packages"
                        :autocomplete="false"
                    />
                </vs-col>
                <vs-col vs-w="3" vs-xs="12" class="px-3">
                    <small class="ml-2"> Date de début </small>
                    <flat-pickr
                        name="starts_at"
                        :config="configDatePicker(null, subscription.ends_at)"
                        class="w-full"
                        v-validate="'required'"
                        v-model="subscription.starts_at"
                    />
                    <small v-show="errors.has('starts_at')" class="text-danger">
                        {{ errors.first("starts_at") }}
                    </small>
                </vs-col>
                <vs-col vs-w="3" vs-xs="12" class="px-3">
                    <small class="ml-2"> Date de fin </small>
                    <flat-pickr
                        name="ends_at"
                        :config="configDatePicker(subscription.starts_at)"
                        v-model="subscription.ends_at"
                        v-validate="'required'"
                        class="w-full"
                    />
                    <small v-show="errors.has('ends_at')" class="text-danger">
                        {{ errors.first("ends_at") }}
                    </small>
                </vs-col>
                <vs-col vs-w="2" vs-xs="12" class="px-3">
                    <small class="ml-2"> Période d'essaie </small>
                    <vs-switch
                        class="mt-2"
                        v-model="subscription.is_trial"
                        name="is_trial"
                    >
                    </vs-switch>
                </vs-col>
            </vs-row>
            <!-- Save & Reset Button -->
            <div class="vx-row">
                <div class="vx-col w-full">
                    <div class="mt-8 flex flex-wrap items-center justify-end">
                        <vs-button
                            class="ml-auto mt-2"
                            @click="addCompany"
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
import CompanyDetails from "@/components/forms/CompanyDetails.vue";
import InfiniteSelect from "@/components/inputs/selects/InfiniteSelect";

// FlatPickr import
import flatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import { French as FrenchLocale } from "flatpickr/dist/l10n/fr.js";

import { Validator } from "vee-validate";
import errorMessage from "./errorValidForm";
Validator.localize("fr", errorMessage);

// Store Module
import moduleCompanyManagement from "@/store/company-management/moduleCompanyManagement.js";
import moduleSubscriptionManagement from "@/store/subscription-management/moduleSubscriptionManagement.js";
import modulePackageManagement from "@/store/package-management/modulePackageManagement.js";

var model = "company";
var modelPlurial = "companies";

export default {
    components: {
        InfiniteSelect,
        flatPickr,
        CompanyDetails
    },
    data() {
        return {
            itemLocal: {
                name: "",
                siret: "",
                code: "",
                type: "",
                contact_firstname: "",
                contact_lastname: "",
                contact_function: "",
                contact_tel1: "",
                contact_tel2: "",
                contact_email: "",
                street_number: "",
                street_name: "",
                postal_code: "",
                city: "",
                country: "",
                authorize_supply: false
            },
            subscription: {
                starts_at: null,
                ends_at: null,
                packages: [],
                is_trial: true
            },
            configDatePicker: (minDate = null, maxDate = null) => ({
                disableMobile: "true",
                dateFormat: "d/m/Y",
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
                this.itemLocal.name !== "" &&
                this.itemLocal.siret !== "" &&
                this.itemLocal.contact_firstname !== "" &&
                this.itemLocal.contact_lastname !== "" &&
                this.itemLocal.contact_function !== "" &&
                (this.itemLocal.contact_tel1 !== "" ||
                    this.itemLocal.contact_tel2 !== "") &&
                this.itemLocal.contact_email !== "" &&
                this.itemLocal.street_number !== "" &&
                this.itemLocal.street_name !== "" &&
                this.itemLocal.postal_code !== "" &&
                this.itemLocal.city !== "" &&
                this.itemLocal.country !== "" &&
                this.itemLocal.authorize_supply !== "" &&
                this.subscription.starts_at &&
                this.subscription.ends_at &&
                this.subscription.packages &&
                this.subscription.packages.length > 0
            );
        },
        isAdmin() {
            return this.$store.state.AppActiveUser.is_admin;
        }
    },
    methods: {
        addCompany() {
            if (this.validateForm) {
                const item = JSON.parse(JSON.stringify(this.itemLocal));
                item.subscription = this.subscription;
                this.$store
                    .dispatch("companyManagement/addItem", item)
                    .then(() => {
                        this.$vs.notify({
                            title: "Ajout d'une société",
                            text: `"${item.name}" ajoutée avec succès`,
                            iconPack: "feather",
                            icon: "icon-alert-circle",
                            color: "success"
                        });
                        this.back();
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
                        this.$vs.loading.close();
                    });
            }
        },
        back() {
            this.$router.push(`/${modelPlurial}`).catch(() => {});
        }
    },
    created() {
        if (!moduleCompanyManagement.isRegistered) {
            this.$store.registerModule(
                "companyManagement",
                moduleCompanyManagement
            );
            moduleCompanyManagement.isRegistered = true;
        }
        if (!moduleSubscriptionManagement.isRegistered) {
            this.$store.registerModule(
                "subscriptionManagement",
                moduleSubscriptionManagement
            );
            moduleSubscriptionManagement.isRegistered = true;
        }
        if (!modulePackageManagement.isRegistered) {
            this.$store.registerModule(
                "packageManagement",
                modulePackageManagement
            );
            modulePackageManagement.isRegistered = true;
        }
    },
    beforeDestroy() {
        moduleCompanyManagement.isRegistered = false;
        this.$store.unregisterModule("companyManagement");
        moduleSubscriptionManagement.isRegistered = false;
        this.$store.unregisterModule("subscriptionManagement");
        modulePackageManagement.isRegistered = false;
        this.$store.unregisterModule("packageManagement");
    }
};
</script>
