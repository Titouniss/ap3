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
        <vs-col vs-w="6" vs-xs="12" class="pr-3">
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
        <vs-col vs-w="6" vs-xs="12" class="pl-3">
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
          <vs-col vs-w="8" vs-xs="12" class="pr-3">
            <v-select
              name="packagesData"
              label="display_name"
              multiple
              v-model="itemLocal.subscription_packages"
              class="w-full"
              autocomplete
              :options="packagesData"
              :reduce="(p) => p.id"
            >
              <template #header>
                <small>Abonnements</small>
              </template>
            </v-select>
          </vs-col>
          <vs-col vs-w="4" vs-xs="12" class="pl-3">
            <small> Période d'essaie </small>
            <vs-switch class="mt-2" v-model="itemLocal.is_trial" />
          </vs-col>
        </vs-row>
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

// Store Module
import moduleCompanyManagement from "@/store/company-management/moduleCompanyManagement.js";
import modulePackageManagement from "@/store/package-management/modulePackageManagement.js";

var model = "company";
var modelPlurial = "companies";

export default {
  components: {
    vSelect,
  },
  data() {
    return {
      itemLocal: {
        name: "",
        siret: "",
        is_trial: true,
        subscription_packages: [],
      },
    };
  },
  computed: {
    validateForm() {
      return (
        !this.errors.any() &&
        this.itemLocal.name !== "" &&
        this.itemLocal.siret !== ""
      );
    },
    isAdmin() {
      const user = this.$store.state.AppActiveUser;
      if (user.roles && user.roles.length > 0) {
        return user.roles.find(
          (r) => r.name === "superAdmin" || r.name === "littleAdmin"
        );
      }

      return false;
    },
    packagesData() {
      return this.$store.state.packageManagement.packages;
    },
  },
  methods: {
    addCompany() {
      if (this.validateForm) {
        const itemLocal = Object.assign({}, this.itemLocal);
        this.$store
          .dispatch("companyManagement/addItem", itemLocal)
          .then(() => {
            this.$vs.notify({
              title: "Ajout d'une société",
              text: `"${itemLocal.name}" ajoutée avec succès`,
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
            this.back();
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
    if (!modulePackageManagement.isRegistered) {
      this.$store.registerModule("packageManagement", modulePackageManagement);
      modulePackageManagement.isRegistered = true;
    }
    this.$store.dispatch("packageManagement/fetchItems").catch((err) => {
      console.error(err);
    });
  },
  beforeDestroy() {
    moduleCompanyManagement.isRegistered = false;
    this.$store.unregisterModule("companyManagement");
    modulePackageManagement.isRegistered = false;
    this.$store.unregisterModule("packageManagement");
  },
};
</script>
