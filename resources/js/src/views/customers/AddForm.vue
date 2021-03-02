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
      <company-details :itemLocal="itemLocal" />
      <div v-if="isAdmin" class="w-full">
        <div class="pt-6 px-3 flex items-end">
          <feather-icon svgClasses="w-6 h-6" icon="LockIcon" class="mr-2" />
          <span class="font-medium text-lg leading-none"> Admin </span>
        </div>
        <vs-divider />
        <div class="w-full px-3">
          <v-select
            label="name"
            v-validate="'required'"
            v-model="itemLocal.company_id"
            :options="companiesData"
            :reduce="(company) => company.id"
            class="w-full mb-4 mt-5"
          >
            <template #header>
              <div class="vs-select--label">Société</div>
            </template>
          </v-select>
        </div>
      </div>
      <!-- Save & Reset Button -->
      <div class="vx-row">
        <div class="vx-col w-full">
          <div class="mt-8 flex flex-wrap items-center justify-end">
            <vs-button
              class="ml-auto mt-2"
              @click="addClient"
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
import lodash from "lodash";
import vSelect from "vue-select";
import moment from "moment";
import CompanyDetails from "@/components/forms/CompanyDetails.vue";

// FlatPickr import
import flatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import { French as FrenchLocale } from "flatpickr/dist/l10n/fr.js";

import { Validator } from "vee-validate";
import errorMessage from "./errorValidForm";
Validator.localize("fr", errorMessage);

// Store Module
import moduleCustomerManagement from "@/store/customer-management/moduleCustomerManagement.js";
import moduleCompanyManagement from "@/store/company-management/moduleCompanyManagement.js";

var model = "customer";
var modelPlurial = "customers";

export default {
  components: {
    vSelect,
    flatPickr,
    CompanyDetails,
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
        company_id: this.isAdmin
          ? null
          : this.$store.state.AppActiveUser.company_id,
      },
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
        this.itemLocal.company_id !== ""
      );
    },
    isAdmin() {
      return this.$store.state.AppActiveUser.is_admin;
    },
    companiesData() {
      return this.$store.getters["companyManagement/getItems"];
    },
  },
  methods: {
    authorizedTo(action, model = modelPlurial) {
      return this.$store.getters.userHasPermissionTo(`${action} ${model}`);
    },
    addClient() {
      if (this.validateForm) {
        const item = JSON.parse(JSON.stringify(this.itemLocal));
        this.$store
          .dispatch("customerManagement/addItem", item)
          .then(() => {
            this.$vs.notify({
              title: "Ajout d'un client",
              text: `"${item.name}" ajoutée avec succès`,
              iconPack: "feather",
              icon: "icon-alert-circle",
              color: "success",
            });
            this.back();
          })
          .catch((error) => {
            this.$vs.notify({
              title: "Error",
              text: error.message,
              iconPack: "feather",
              icon: "icon-alert-circle",
              color: "danger",
            });
          })
          .finally(() => {
            this.$vs.loading.close();
          });
      }
    },
    back() {
      this.$router.push(`/${modelPlurial}`).catch(() => {});
    },
  },
  created() {
    if (!moduleCustomerManagement.isRegistered) {
      this.$store.registerModule(
        "customerManagement",
        moduleCustomerManagement
      );
      moduleCustomerManagement.isRegistered = true;
    }
    if (!moduleCompanyManagement.isRegistered) {
      this.$store.registerModule("companyManagement", moduleCompanyManagement);
      moduleCompanyManagement.isRegistered = true;
    }

    if (this.authorizedTo("read", "companies")) {
      this.$store.dispatch("companyManagement/fetchItems").catch((err) => {
        console.error(err);
      });
    }
  },
  beforeDestroy() {
    moduleCustomerManagement.isRegistered = false;
    moduleCompanyManagement.isRegistered = false;

    this.$store.unregisterModule("customerManagement");
    this.$store.unregisterModule("companyManagement");
  },
};
</script>
