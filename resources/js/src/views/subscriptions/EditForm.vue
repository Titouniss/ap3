<template>
  <vs-prompt
    title="Ajouter un abonnement"
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
      <vs-col vs-w="12" vs-xs="12" class="pb-3">
        <small class="ml-2"> Annulé </small>
        <vs-switch
          color="danger"
          vs-icon-off="close"
          vs-icon-on="done"
          v-model="is_cancelled"
        ></vs-switch>
      </vs-col>
    </vs-row>
  </vs-prompt>
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
    itemId: {
      type: Number,
      required: true,
    },
  },
  data() {
    return {
      itemLocal: {},
      is_cancelled: false,

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
        this.itemLocal.start_date &&
        this.itemLocal.end_date &&
        this.itemLocal.packages &&
        this.itemLocal.packages.length > 0
      );
    },
    packagesData() {
      return this.$store.state.subscriptionManagement.packages;
    },
    activePrompt: {
      get() {
        return this.itemId && this.itemId > 0 ? true : false;
      },
      set(value) {
        this.$store
          .dispatch("subscriptionManagement/editItem", {})
          .catch((err) => {
            console.error(err);
          });
      },
    },
  },
  methods: {
    init() {
      this.itemLocal = JSON.parse(
        JSON.stringify(
          this.$store.getters["subscriptionManagement/getItem"](this.itemId)
        )
      );
      this.itemLocal.packages = this.itemLocal.packages.map((p) => p.id);
      this.is_cancelled = this.itemLocal.state === "cancelled";
    },
    submitItem() {
      this.$validator.validateAll().then((result) => {
        if (result) {
          const item = JSON.parse(JSON.stringify(this.itemLocal));
          item.start_date = item.start_date.split(" ").shift();
          item.end_date = item.end_date.split(" ").shift();
          if (this.is_cancelled) {
            item.is_cancelled = true;
          }
          this.$store
            .dispatch("subscriptionManagement/updateItem", item)
            .then(() => {
              this.$vs.notify({
                title: "Modification d'un abonnement",
                text: `Abonnement du ${moment(item.start_date).format(
                  "DD/MM/YYYY"
                )} au ${moment(item.end_date).format(
                  "DD/MM/YYYY"
                )} modifié avec succès`,
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
            });
        }
      });
    },
  },
  created() {
    this.init();
  },
};
</script>
