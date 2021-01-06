<!-- =========================================================================================
  File Name: CompanyEdit.vue
  Description: company Edit Page
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/role/pixinvent
========================================================================================== -->
<template>
  <div>
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
      <div v-if="isAdmin" class="w-full">
        <div class="flex items-end px-3">
          <feather-icon svgClasses="w-6 h-6" icon="LockIcon" class="mr-2" />
          <span class="font-medium text-lg leading-none"> Admin </span>
        </div>
        <vs-divider />
        <vs-row vs-justify="flex-start" vs-type="flex" vs-w="12">
          <vs-col vs-w="2" vs-xs="12" class="px-3">
            <small class="ml-2"> Période d'essaie </small>
            <vs-switch
              class="mt-1"
              v-model="itemLocal.is_trial"
              name="is_trial"
            >
            </vs-switch>
          </vs-col>
        </vs-row>
      </div>
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
    <div v-if="itemLocal.id" class="mt-5">
      <subscriptions-index :companyId="itemLocal.id"></subscriptions-index>
    </div>
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

// Store Module
import moduleCompanyManagement from "@/store/company-management/moduleCompanyManagement.js";

import SubscriptionsIndex from "../subscriptions/Index";

var model = "company";
var modelPlurial = "companies";

export default {
  components: {
    vSelect,
    flatPickr,
    SubscriptionsIndex,
  },
  props: {
    itemId: {
      type: Number,
    },
  },
  data() {
    return {
      itemLocal: {
        name: "",
        siret: "",
        is_trial: false,
      },
    };
  },
  computed: {
    isAdmin() {
      const user = this.$store.state.AppActiveUser;
      if (user.roles && user.roles.length > 0) {
        return user.roles.find((r) => r.name === "superAdmin");
      }
      return false;
    },
    validateForm() {
      return (
        !this.errors.any() &&
        this.itemLocal.name !== "" &&
        this.itemLocal.siret !== "" &&
        (!this.selectedSubscriptionType ||
          (this.subscription.start_date &&
            this.subscription.end_date &&
            this.subscription.packages &&
            this.subscription.packages.length > 0))
      );
    },
  },
  methods: {
    init() {
      this.$vs.loading();
      let id = this.itemId
        ? this.itemId
        : parseInt(this.$route.params["companyId"]);

      this.$store
        .dispatch("companyManagement/fetchItem", id)
        .then((res) => {
          const item = JSON.parse(JSON.stringify(res.data.success));
          if (item.subscription != null) {
            this.subscription = this.itemLocal.subscription;
            item.subscription = null;
          }
          this.itemLocal = item;
          console.log(item);
        })
        .catch((error) => {
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
              color: "success",
            });
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
            if (this.itemId) {
              this.$router.push(`/${modelPlurial}`).catch(() => {});
            }
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
    this.$store.dispatch("companyManagement/fetchItems").catch((err) => {
      console.error(err);
    });
    this.init();
  },
  beforeDestroy() {
    moduleCompanyManagement.isRegistered = false;
    this.$store.unregisterModule("companyManagement");
  },
};
</script>
