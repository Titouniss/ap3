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
            <!-- Save & Reset Button -->
            <div class="vx-row">
                <div class="vx-col w-full">
                    <div class="mt-8 flex flex-wrap items-center justify-end">
                        <vs-button
                            class="ml-auto mt-2"
                            @click="submitItem"
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
        <div v-if="itemLocal.id && isAdmin" class="mt-5">
            <subscriptions-index
                :companyId="itemLocal.id"
            ></subscriptions-index>
        </div>
    </div>
</template>

<script>
import CompanyDetails from "@/components/forms/CompanyDetails.vue";

// FlatPickr import
import flatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import { French as FrenchLocale } from "flatpickr/dist/l10n/fr.js";

import { Validator } from "vee-validate";
import errorMessage from "./errorValidForm";
Validator.localize("fr", errorMessage);

// Store Module
import moduleCompanyManagement from "@/store/company-management/moduleCompanyManagement.js";

import SubscriptionsIndex from "../subscriptions/Index";

var model = "company";
var modelPlurial = "companies";

export default {
    components: {
        flatPickr,
        SubscriptionsIndex,
        CompanyDetails
    },
    props: {
        itemId: {
            type: Number
        }
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
                this.itemLocal.country !== ""
                && this.itemLocal.authorize_supply !== ""
                
            );
        }
    },
    methods: {
        init() {
            this.$vs.loading();
            let id = this.itemId
                ? this.itemId
                : parseInt(this.$route.params["companyId"]);

            this.$store
                .dispatch("companyManagement/fetchItem", id)
                .then(data => {
                    const item = data.payload;
                    if (item.subscription != null) {
                        this.subscription = this.itemLocal.subscription;
                        item.subscription = null;
                    }
                    const payload = data.payload;
                    for (const prop in payload) {
                        if (payload[prop]) {
                            this.itemLocal[prop] = payload[prop];
                        }
                    }
                })
                .catch(error => {
                    console.log(error);
                })
                .finally(() => this.$vs.loading.close());
        },
        submitItem() {
            if (this.validateForm) {
                const item = JSON.parse(JSON.stringify(this.itemLocal));
                this.$store
                    .dispatch("companyManagement/updateItem", item)
                    .then(() => {
                        this.$vs.notify({
                            title: "Modification d'une société",
                            text: `"${this.itemLocal.name}" modifiée avec succès`,
                            iconPack: "feather",
                            icon: "icon-alert-circle",
                            color: "success"
                        });
                        if (this.isAdmin) {
                            this.back();
                        }
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
            this.$router.push(`/${this.isAdmin ? modelPlurial : ""}`);
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

        this.init();
    },
    beforeDestroy() {
        moduleCompanyManagement.isRegistered = false;
        this.$store.unregisterModule("companyManagement");
    }
};
</script>
