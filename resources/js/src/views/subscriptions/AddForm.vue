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
      <vs-row vs-justify="flex-start" vs-type="flex" vs-w="12" class="px-3">
        <vs-col vs-w="12" class="pb-3">
          <v-select
            name="packages"
            label="display_name"
            multiple
            v-model="itemLocal.packages"
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
        <vs-col vs-w="12" class="pb-3">
          <small class="ml-2"> Date de début </small>
          <flat-pickr
            name="start_date"
            v-validate="'required'"
            :config="configDatePicker(null, itemLocal.end_date)"
            class="w-full"
            v-model="itemLocal.start_date"
          />
          <small v-show="errors.has('start_date')" class="text-danger">
            {{ errors.first("start_date") }}
          </small>
        </vs-col>
        <vs-col vs-w="12" vs-xs="12" class="pb-3">
          <small class="ml-2"> Date de fin </small>
          <flat-pickr
            name="end_date"
            v-validate="'required'"
            :config="configDatePicker(itemLocal.start_date)"
            v-model="itemLocal.end_date"
            class="w-full"
          />
          <small v-show="errors.has('end_date')" class="text-danger">
            {{ errors.first("end_date") }}
          </small>
        </vs-col>
      </vs-row>
    </vs-prompt>
  </div>
</template>

<script>
import { Validator } from "vee-validate";
import errorMessage from "./errorValidForm";
import vSelect from "vue-select";

// FlatPickr import
import flatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import { French as FrenchLocale } from "flatpickr/dist/l10n/fr.js";

import moment from "moment";

// register custom messages
Validator.localize("fr", errorMessage);

export default {
  components: {
    vSelect,
    flatPickr,
  },
  props: {
    companyId: {
      type: Number,
      required: true,
    },
  },
  data() {
    return {
      activePrompt: false,

      itemLocal: {
        start_date: null,
        end_date: null,
        packages: [],
      },
      configDatePicker: (minDate = null, maxDate = null) => ({
        altInput: true,
        altFormat: "d/m/Y",
        disableMobile: "true",
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
        this.companyId &&
        this.itemLocal.start_date &&
        this.itemLocal.end_date &&
        this.itemLocal.packages &&
        this.itemLocal.packages.length > 0
      );
    },
    packagesData() {
      return this.$store.state.subscriptionManagement.packages;
    },
  },
  methods: {
    clearFields() {
      Object.assign(this.itemLocal, {
        start_date: null,
        end_date: null,
        packages: [],
      });
    },
    addSubscription() {
      this.$validator.validateAll().then((result) => {
        if (result) {
          const item = JSON.parse(JSON.stringify(this.itemLocal));
          item.company_id = this.companyId;
          this.$store
            .dispatch("subscriptionManagement/addItem", item)
            .then(() => {
              this.$vs.notify({
                title: "Ajout d'un abonnement",
                text: `Abonnement du ${moment(item.start_date).format(
                  "DD/MM/YYYY"
                )} au ${moment(item.end_date).format(
                  "DD/MM/YYYY"
                )} ajouté avec succès`,
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
              this.clearFields();
              this.$vs.loading.close();
            });
        }
      });
    },
  },
};
</script>
