<template>
  <div class="p-3 mb-4 mr-4">
    <vs-button @click="activePrompt = true" class="w-full">Ajouter un projet</vs-button>
    <vs-prompt
      title="Ajouter un projet"
      accept-text="Ajouter"
      cancel-text="Annuler"
      button-cancel="border"
      @cancel="clearFields"
      @accept="addProject"
      @close="clearFields"
      :is-valid="validateForm"
      :active.sync="activePrompt"
    >
      <div>
        <form>
          <div class="vx-row">
            <div class="vx-col w-full">
              <div class="vx-row mt-4" v-if="!disabled">
                <div class="vx-col w-full">
                  <div class="flex items-end px-3">
                    <feather-icon svgClasses="w-6 h-6" icon="LockIcon" class="mr-2" />
                    <span class="font-medium text-lg leading-none">Admin</span>
                  </div>
                  <vs-divider />
                  <v-select
                    label="name"
                    @input="updateCustomersList"
                    v-validate="'required'"
                    v-model="itemLocal.company"
                    :options="companiesData"
                    class="w-full"
                  >
                    <template #header>
                      <div class="vs-select--label">Société</div>
                    </template>
                  </v-select>
                  <vs-divider />
                </div>
              </div>
              <vs-input
                v-validate="'required|max:255'"
                label="Nom du projet"
                name="name"
                class="w-full mb-4"
                placeholder="Nom"
                v-model="itemLocal.name"
                :color="!errors.has('name') ? 'success' : 'danger'"
              />
              <span
                class="text-danger text-sm"
                v-show="errors.has('name')"
              >{{ errors.first('name') }}</span>
              <vs-col>
                <vs-row>
                  <small class="vs-row date-label">Couleur</small>
                </vs-row>

                <vs-row class="pb-2 pl-2">
                  <v-swatches
                    clas="vs-row"
                    v-model="itemLocal.color"
                    :swatches="colors"
                    swatch-size="40"
                  ></v-swatches>
                </vs-row>
              </vs-col>
              <div class="my-4">
                <small class="date-label">Date de livraison prévue</small>
                <datepicker
                  class="pickadate"
                  :disabledDates="{ to: new Date(Date.now() - 8640000) }"
                  :language="langFr"
                  name="date"
                  v-model="itemLocal.date"
                  :color="validateForm ? 'success' : 'danger'"
                ></datepicker>
              </div>
              <div class="my-4" v-if="itemLocal.company != null">
                <v-select
                  v-model="itemLocal.customer"
                  label="name"
                  :options="customersDataFiltered"
                  :multiple="false"
                  class="w-full"
                >
                  <template #header>
                    <div class="vs-select--label">Client</div>
                  </template>
                  <template #option="customer">
                    <span>{{ customer.professional === 1 ? customer.name : customer.lastname}}</span>
                  </template>
                </v-select>
              </div>
            </div>
          </div>
        </form>
      </div>
    </vs-prompt>
  </div>
</template>

<script>
import Datepicker from "vuejs-datepicker";
import { fr } from "vuejs-datepicker/src/locale";
import moment from "moment";
import { Validator } from "vee-validate";
import errorMessage from "./errorValidForm";
import vSelect from "vue-select";

import VSwatches from "vue-swatches";
import "vue-swatches/dist/vue-swatches.css";

import { project_colors } from "../../../themeConfig";

// register custom messages
Validator.localize("fr", errorMessage);

export default {
  components: {
    vSelect,
    Datepicker,
    VSwatches,
  },
  data() {
    return {
      activePrompt: false,
      langFr: fr,

      itemLocal: {
        name: "",
        date: new Date(),
        customer: null,
        company:
          this.activeUserRole() != "superAdmin"
            ? this.$store.state.AppActiveUser.company.id
            : null,
        company:
          this.activeUserRole() != "superAdmin"
            ? this.$store.state.AppActiveUser.company
            : null,
        color: "",
      },
      colors: project_colors,

      customersDataFiltered: null,
    };
  },
  computed: {
    validateForm() {
      return (
        !this.errors.any() &&
        this.itemLocal.name != "" &&
        this.itemLocal.company != null
      );
    },
    companiesData() {
      return this.$store.state.companyManagement.companies;
    },
    customersData() {
      let customers = this.filterItemsAdmin(
        this.$store.state.customerManagement.customers
      );
      this.customersDataFiltered = customers;
      return customers;
    },
    disabled() {
      const user = this.$store.state.AppActiveUser;
      if (user.roles && user.roles.length > 0) {
        if (
          user.roles.find(
            (r) => r.name === "superAdmin" || r.name === "littleAdmin"
          )
        ) {
          return false;
        } else {
          this.itemLocal.company_id = user.company_id;
          return true;
        }
      } else return true;
    },
  },
  methods: {
    clearFields() {
      this.itemLocal = {
        name: "",
        date: new Date(),
        customer: null,
        company_id: null,
        company: null,
        color: "",
      };

      this.customersDataFiltered = null;
    },
    addProject() {
      this.$validator.validateAll().then((result) => {
        this.itemLocal.date = moment(this.itemLocal.date).format("YYYY-MM-DD");
        this.itemLocal.company
          ? (this.itemLocal.company_id = this.itemLocal.company.id)
          : null;
        this.itemLocal.customer
          ? (this.itemLocal.customer_id = this.itemLocal.customer.id)
          : null;

        if (result) {
          this.$store
            .dispatch(
              "projectManagement/addItem",
              Object.assign({}, this.itemLocal)
            )
            .then((response) => {
              this.clearFields();
              this.$vs.loading.close();
              this.$vs.notify({
                title: "Ajout d'un projet",
                text: `"${response.data.success.name}" ajouté avec succès`,
                iconPack: "feather",
                icon: "icon-alert-circle",
                color: "success",
              });
            })
            .catch((error) => {
              this.$vs.loading.close();
              this.$vs.notify({
                title: "Error",
                text: error.message,
                iconPack: "feather",
                icon: "icon-alert-circle",
                color: "danger",
              });
            });
        }
      });
    },
    filterItemsAdmin(items) {
      let filteredItems = [];
      const user = this.$store.state.AppActiveUser;
      if (user.roles && user.roles.length > 0) {
        if (
          user.roles.find(
            (r) => r.name === "superAdmin" || r.name === "littleAdmin"
          )
        ) {
          filteredItems = items.filter(
            (item) => item.company_id === this.itemLocal.company.id
          );
        } else {
          filteredItems = items;
        }
      }
      return filteredItems;
    },
    updateCustomersList() {
      this.itemLocal.customer = null;
      this.customersDataFiltered = this.filterItemsAdmin(
        this.$store.state.customerManagement.customers
      );

      // Parse label
      this.customersDataFiltered.map(function (c) {
        return (c.name = c.professional === 1 ? c.name : c.lastname);
      });
    },
    activeUserRole() {
      const user = this.$store.state.AppActiveUser;
      if (user.roles && user.roles.length > 0) {
        return user.roles[0].name;
      }
      return false;
    },
  },
  mounted() {},
};
</script>
