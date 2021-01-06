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
      <vs-row vs-justify="center" vs-type="flex" vs-w="12" class="mb-6">
        <vs-col vs-w="6" vs-xs="12" class="px-3">
          <vs-input
            v-validate="'max:255|alpha_dash|required'"
            name="name"
            class="w-full"
            label="Nom de la société"
            v-model="itemLocal.name"
            :success="itemLocal.name.length > 0 && !errors.first('name')"
            :danger="errors.has('name')"
            :danger-text="errors.first('name')"
          />
        </vs-col>
        <vs-col vs-w="6" vs-xs="12" class="px-3">
          <vs-input
            v-validate="'required|numeric|min:14|max:14'"
            name="siret"
            class="w-full"
            label="Siret"
            v-model="itemLocal.siret"
            :success="itemLocal.siret.length > 0 && !errors.first('siret')"
            :danger="errors.has('siret')"
            :danger-text="errors.first('siret')"
          />
        </vs-col>
      </vs-row>
      <div class="pt-6 px-3 flex items-end">
        <feather-icon svgClasses="w-6 h-6" icon="UserIcon" class="mr-2" />
        <span class="font-medium text-lg leading-none"> Contact </span>
      </div>
      <vs-divider />
      <vs-row vs-justify="center" vs-type="flex" vs-w="12" class="mb-6">
        <vs-col vs-w="6" vs-xs="12" class="px-3">
          <vs-input
            v-validate="'max:255|required'"
            name="contact_firstname"
            class="w-full"
            label="Prénom"
            v-model="itemLocal.contact_firstname"
            :success="
              itemLocal.contact_firstname.length > 0 &&
              !errors.first('contact_firstname')
            "
            :danger="errors.has('contact_firstname')"
            :danger-text="errors.first('contact_firstname')"
          />
        </vs-col>
        <vs-col vs-w="6" vs-xs="12" class="px-3">
          <vs-input
            v-validate="'max:255|required'"
            name="contact_lastname"
            class="w-full"
            label="Nom"
            v-model="itemLocal.contact_lastname"
            :success="
              itemLocal.contact_lastname.length > 0 &&
              !errors.first('contact_lastname')
            "
            :danger="errors.has('contact_lastname')"
            :danger-text="errors.first('contact_lastname')"
          />
        </vs-col>
      </vs-row>
      <vs-row vs-justify="center" vs-type="flex" vs-w="12" class="mb-6">
        <vs-col vs-w="4" vs-xs="12" class="px-3">
          <vs-input
            v-validate="'max:255|email|required'"
            name="contact_email"
            class="w-full"
            label="Émail"
            v-model="itemLocal.contact_email"
            :success="
              itemLocal.contact_email.length > 0 &&
              !errors.first('contact_email')
            "
            :danger="errors.has('contact_email')"
            :danger-text="errors.first('contact_email')"
          />
        </vs-col>
        <vs-col vs-w="4" vs-xs="12" class="px-3">
          <vs-input
            v-validate="`max:50${!itemLocal.contact_tel2 ? '|required' : ''}`"
            name="contact_tel1"
            class="w-full"
            label="Téléphone fixe"
            v-model="itemLocal.contact_tel1"
            :success="
              itemLocal.contact_tel1.length > 0 && !errors.first('contact_tel1')
            "
            :danger="errors.has('contact_tel1')"
            :danger-text="errors.first('contact_tel1')"
          />
        </vs-col>
        <vs-col vs-w="4" vs-xs="12" class="px-3">
          <vs-input
            v-validate="`max:50${!itemLocal.contact_tel1 ? '|required' : ''}`"
            name="contact_tel2"
            class="w-full"
            label="Téléphone mobile"
            v-model="itemLocal.contact_tel2"
            :success="
              itemLocal.contact_tel2.length > 0 && !errors.first('contact_tel2')
            "
            :danger="errors.has('contact_tel2')"
            :danger-text="errors.first('contact_tel2')"
          />
        </vs-col>
      </vs-row>
      <div class="pt-6 px-3 flex items-end">
        <feather-icon svgClasses="w-6 h-6" icon="MapIcon" class="mr-2" />
        <span class="font-medium text-lg leading-none"> Adresse </span>
      </div>
      <vs-divider />
      <vs-row vs-justify="center" vs-type="flex" vs-w="12" class="mb-6">
        <vs-col vs-w="6" vs-xs="12" class="px-3">
          <vs-input
            v-validate="'max:10|required'"
            name="street_number"
            class="w-full"
            label="Numéro"
            v-model="itemLocal.street_number"
            :success="
              itemLocal.street_number.length > 0 &&
              !errors.first('street_number')
            "
            :danger="errors.has('street_number')"
            :danger-text="errors.first('street_number')"
          />
        </vs-col>
        <vs-col vs-w="6" vs-xs="12" class="px-3">
          <vs-input
            v-validate="'max:255|required'"
            name="street_name"
            class="w-full"
            label="Rue"
            v-model="itemLocal.street_name"
            :success="
              itemLocal.street_name.length > 0 && !errors.first('street_name')
            "
            :danger="errors.has('street_name')"
            :danger-text="errors.first('street_name')"
          />
        </vs-col>
      </vs-row>
      <vs-row vs-justify="center" vs-type="flex" vs-w="12" class="mb-6">
        <vs-col vs-w="4" vs-xs="12" class="px-3">
          <vs-input
            v-validate="'max:15|required'"
            name="postal_code"
            class="w-full"
            label="Code postal"
            v-model="itemLocal.postal_code"
            :success="
              itemLocal.postal_code.length > 0 && !errors.first('postal_code')
            "
            :danger="errors.has('postal_code')"
            :danger-text="errors.first('postal_code')"
          />
        </vs-col>
        <vs-col vs-w="4" vs-xs="12" class="px-3">
          <vs-input
            v-validate="'max:255|required'"
            name="city"
            class="w-full"
            label="Ville"
            v-model="itemLocal.city"
            :success="itemLocal.city.length > 0 && !errors.first('city')"
            :danger="errors.has('city')"
            :danger-text="errors.first('city')"
          />
        </vs-col>
        <vs-col vs-w="4" vs-xs="12" class="px-3">
          <vs-input
            v-validate="'max:255|required'"
            name="country"
            class="w-full"
            label="Pays"
            v-model="itemLocal.country"
            :success="itemLocal.country.length > 0 && !errors.first('country')"
            :danger="errors.has('country')"
            :danger-text="errors.first('country')"
          />
        </vs-col>
      </vs-row>
      <div v-if="isAdmin" class="w-full">
        <div class="pt-6 px-3 flex items-end">
          <feather-icon svgClasses="w-6 h-6" icon="LockIcon" class="mr-2" />
          <span class="font-medium text-lg leading-none"> Admin </span>
        </div>
        <vs-divider />
        <div class="w-full px-3">
          <vs-row vs-justify="flex-start" vs-type="flex" vs-w="12" class="mb-6">
            <vs-col vs-w="6" vs-xs="12" class="px-3">
              <vs-input
                v-validate="'max:255|required'"
                name="code"
                class="w-full"
                label="N° client"
                v-model="itemLocal.code"
                :success="itemLocal.code.length > 0 && !errors.first('code')"
                :danger="errors.has('code')"
                :danger-text="errors.first('code')"
              />
            </vs-col>
            <vs-col vs-w="4" vs-xs="12" class="px-3">
              <vs-input
                v-validate="'max:255|required'"
                name="type"
                class="w-full"
                label="Type de société"
                v-model="itemLocal.type"
                :success="itemLocal.type.length > 0 && !errors.first('type')"
                :danger="errors.has('type')"
                :danger-text="errors.first('type')"
              />
            </vs-col>
            <vs-col vs-w="2" vs-xs="12" class="px-3">
              <small class="ml-2"> Période d'essaie </small>
              <vs-switch
                class="mt-2"
                v-model="itemLocal.is_trial"
                name="is_trial"
              >
              </vs-switch>
            </vs-col>
          </vs-row>
          <div class="pt-6 px-3 flex items-end">
            <feather-icon
              svgClasses="w-6 h-6"
              icon="FilePlusIcon"
              class="mr-2"
            />
            <span class="font-medium text-lg leading-none"> Abonnement </span>
          </div>
          <vs-divider />
          <vs-row vs-justify="flex-start" vs-type="flex" vs-w="12">
            <vs-col vs-w="6" vs-xs="12" class="px-3">
              <v-select
                name="packages"
                label="display_name"
                multiple
                v-model="subscription.packages"
                class="w-full"
                autocomplete
                v-validate="'required'"
                :options="packagesData"
                :reduce="(p) => p.id"
              >
                <template #header>
                  <small class="ml-2"> Paquets </small>
                </template>
              </v-select>
              <small v-show="errors.has('packages')" class="text-danger">
                {{ errors.first("packages") }}
              </small>
            </vs-col>
            <vs-col vs-w="3" vs-xs="12" class="px-3">
              <small class="ml-2"> Date de début </small>
              <flat-pickr
                name="start_date"
                :config="configDatePicker(null, subscription.end_date)"
                class="w-full"
                v-validate="'required'"
                v-model="subscription.start_date"
              />
              <small v-show="errors.has('start_date')" class="text-danger">
                {{ errors.first("start_date") }}
              </small>
            </vs-col>
            <vs-col vs-w="3" vs-xs="12" class="px-3">
              <small class="ml-2"> Date de fin </small>
              <flat-pickr
                name="end_date"
                :config="configDatePicker(subscription.start_date)"
                v-model="subscription.end_date"
                v-validate="'required'"
                class="w-full"
              />
              <small v-show="errors.has('end_date')" class="text-danger">
                {{ errors.first("end_date") }}
              </small>
            </vs-col>
          </vs-row>
        </div>
      </div>
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
import lodash from "lodash";
import vSelect from "vue-select";
import moment from "moment";

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

var model = "company";
var modelPlurial = "companies";

export default {
  components: {
    vSelect,
    flatPickr,
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
        contact_tel1: "",
        contact_tel2: "",
        contact_email: "",
        street_number: "",
        street_name: "",
        postal_code: "",
        city: "",
        country: "",
        is_trial: true,
      },
      subscription: {
        start_date: null,
        end_date: null,
        packages: [],
      },
      configDatePicker: (minDate = null, maxDate = null) => ({
        disableMobile: "true",
        dateFormat: "d/m/Y",
        locale: FrenchLocale,
        minDate,
        maxDate,
      }),
    };
  },
  computed: {
    validateForm() {
      return (
        !this.errors.any() &&
        this.itemLocal.name !== "" &&
        this.itemLocal.siret !== "" &&
        this.itemLocal.code !== "" &&
        this.itemLocal.type !== "" &&
        this.itemLocal.contact_firstname !== "" &&
        this.itemLocal.contact_lastname !== "" &&
        this.itemLocal.contact_tel1 !== "" &&
        this.itemLocal.contact_tel2 !== "" &&
        this.itemLocal.contact_email !== "" &&
        this.itemLocal.street_number !== "" &&
        this.itemLocal.street_name !== "" &&
        this.itemLocal.postal_code !== "" &&
        this.itemLocal.city !== "" &&
        this.itemLocal.country !== "" &&
        this.subscription.start_date &&
        this.subscription.end_date &&
        this.subscription.packages &&
        this.subscription.packages.length > 0
      );
    },
    isAdmin() {
      const user = this.$store.state.AppActiveUser;
      if (user.roles && user.roles.length > 0) {
        return user.roles.find((r) => r.name === "superAdmin");
      }

      return false;
    },
    subscriptionsData() {
      return this.$store.state.subscriptionManagement.subscriptions;
    },
    packagesData() {
      return this.$store.state.subscriptionManagement.packages;
    },
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
    if (!moduleCompanyManagement.isRegistered) {
      this.$store.registerModule("companyManagement", moduleCompanyManagement);
      moduleCompanyManagement.isRegistered = true;
    }
    if (!moduleSubscriptionManagement.isRegistered) {
      this.$store.registerModule(
        "subscriptionManagement",
        moduleSubscriptionManagement
      );
      moduleSubscriptionManagement.isRegistered = true;
    }
    this.$store.dispatch("subscriptionManagement/fetchItems").catch((err) => {
      console.error(err);
    });
    this.$store
      .dispatch("subscriptionManagement/fetchPackages")
      .catch((err) => {
        console.error(err);
      });
  },
  beforeDestroy() {
    moduleCompanyManagement.isRegistered = false;
    this.$store.unregisterModule("companyManagement");
    moduleSubscriptionManagement.isRegistered = false;
    this.$store.unregisterModule("subscriptionManagement");
  },
};
</script>
