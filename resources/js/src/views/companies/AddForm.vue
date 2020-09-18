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
    <vx-card>
      <vs-row vs-justify="flex-start" vs-type="flex" vs-w="12" v-if="isAdmin">
        <vs-col vs-w="12" vs-xs="12" class="mt-6 px-6">
          <div class="vx-row mt-4">
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
              
              <div class="mt-6 mb-3">
                  <small class="ml-1 mb-2" for
                    >Période d'essaie</small
                  >
                  <vs-switch v-model="itemLocal.is_trial" />
                </div>
              </div>
              <vs-divider />
            </div>
          </vs-col>
        </vs-row>
      <vs-row vs-justify="center" vs-type="flex" vs-w="12">
        <vs-col vs-w="6" vs-xs="12" class="mt-3 px-6">
          <vs-input
            v-validate="'max:255|alpha_dash|required'"
            name="name"
            class="w-full mb-4 mt-5"
            label="Nom de la société"
            v-model="itemLocal.name"
            :color="!errors.has('name') ? 'success' : 'danger'"
          />
          <span
            class="text-danger text-sm"
            v-show="errors.has('name')"
          >{{ errors.first("name") }}</span>
        </vs-col>
        <vs-col vs-w="6" vs-xs="12" class="mt-3 px-6">
          <vs-input
            v-validate="'required|numeric|min:14|max:14'"
            name="siret"
            class="w-full mb-4 mt-5"
            label="Siret"
            v-model="itemLocal.siret"
          />
          <span
            class="text-danger text-sm"
            v-show="errors.has('siret')"
            >{{ errors.first("siret") }}</span
          >
        </vs-col>
      </vs-row>
      <!-- Save & Reset Button -->
      <div class="vx-row">
        <div class="vx-col w-full">
          <div class="mt-8 flex flex-wrap items-center justify-end">
            <vs-button class="ml-auto mt-2" @click="addCompany" :disabled="!validateForm">Ajouter</vs-button>
            <vs-button class="ml-4 mt-2" type="border" color="warning" @click="back">Annuler</vs-button>
          </div>
        </div>
      </div>
    </vx-card>
  </div>
</template>

<script>
import lodash from "lodash";

// Store Module
import moduleCompanyManagement from "@/store/company-management/moduleCompanyManagement.js";

var model = "company";
var modelPlurial = "companies";

export default {
  data() {
        return {
            itemLocal: {
                name: "",
                siret: "",
                is_trial: true
            }
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
                    r => r.name === "superAdmin" || r.name === "littleAdmin"
                );
            }

            return false;
        }
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
                        this.$vs.loading.close();
                        this.back()
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
  },
  beforeDestroy() {
    moduleCompanyManagement.isRegistered = false;
    this.$store.unregisterModule("companyManagement");
  }
};
</script>