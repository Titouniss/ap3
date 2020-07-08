<template>
  <div class="p-3 mr-4">
    <div class="text-center">
      <small
        class="date-label pl-1 mr-4"
      >Heures supplémentaires à utiliser : {{this.disabled ? "0" : this.dealingHours.overtimes - this.dealingHours.usedHours}}</small>
      <vs-button
        :disabled="disabled"
        @click="activePrompt = true"
        class="w-1/3"
      >Récupération d'heures</vs-button>
    </div>
    <vs-prompt
      title="Récupéré des heures"
      accept-text="Récupérer"
      cancel-text="Annuler"
      button-cancel="border"
      @cancel="clearFields"
      @accept="addUsedHours"
      @close="clearFields"
      :is-valid="validateForm"
      :active.sync="activePrompt"
    >
      <div>
        <form>
          <div class="vx-row">
            <div class="vx-col w-full">
              <vs-row vs-justify="center" vs-type="flex" vs-w="12">
                <vs-col vs-w="8" vs-xs="12" class="px-6">
                  <small class="date-label pl-1">Date</small>
                  <flat-pickr
                    :config="configDatePicker()"
                    class="w-full mb-4"
                    v-model="itemLocal.date"
                  />
                  <small class="date-label pl-1">Durée</small>
                  <flat-pickr
                    v-validate="'required'"
                    :config="configTimePicker()"
                    class="w-full mb-4"
                    v-model="itemLocal.used_hours"
                  />
                  <small class="date-label pl-1">Type</small>
                  <vs-select
                    v-validate="'required'"
                    name="used_type"
                    v-model="itemLocal.used_type"
                    class="w-full"
                    autocomplete
                  >
                    <vs-select-item
                      :key="index"
                      :value="item.name"
                      :text="item.name"
                      v-for="(item,index) in used_types"
                    />
                  </vs-select>
                </vs-col>
              </vs-row>
            </div>
          </div>
        </form>
      </div>
    </vs-prompt>
  </div>
</template>

<script>
// register custom messages
import { Validator } from "vee-validate";
import errorMessage from "./errorValidForm";
Validator.localize("fr", errorMessage);

import vSelect from "vue-select";

// Store Module
import moduleUserManagement from "@/store/user-management/moduleUserManagement.js";
import moduleDealingHoursManagement from "@/store/dealing-hours-management/moduleDealingHoursManagement.js";

// FlatPickr import
import flatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";
import { French as FrenchLocale } from "flatpickr/dist/l10n/fr.js";

export default {
  props: {
    user: {
      type: Object
    },
    dealingHours: {
      type: Object
    }
  },
  data() {
    const user = this.$store.state.AppActiveUser;

    return {
      activePrompt: false,

      itemLocal: {
        date: null,
        overtimes: -7,
        overtimes_to_use: 0,
        used_hours: null,
        used_type: "",
        user_id: user.id
      },
      used_types: [{ name: "récupérées" }, { name: "Payées" }],
      // FlatPickr config
      configTimePicker: () => ({
        disableMobile: "true",
        enableTime: true,
        locale: FrenchLocale,
        noCalendar: true,
        defaultHour: 0,
        hourIncrement: true
      }),
      configDatePicker: () => ({
        disableMobile: "true",
        enableTime: false,
        locale: FrenchLocale,
        altFormat: "j F Y",
        altInput: true
      })
    };
  },
  components: {
    flatPickr,
    vSelect
  },
  computed: {
    validateForm() {
      return (
        !this.errors.any() &&
        this.itemLocal.date != null &&
        this.itemLocal.used_hours != null &&
        this.itemLocal.used_type != "" &&
        this.itemLocal.user_id != null
      );
    },
    disabled: {
      get() {
        return this.dealingHours.missHours === 0 &&
          this.dealingHours.overtimes > this.dealingHours.usedHours
          ? false
          : true;
      },
      set(val) {}
    }
  },
  methods: {
    clearFields() {
      Object.assign(this.itemLocal, {
        date: null,
        overtimes: -7,
        overtimes_to_use: 0,
        used_hours: null,
        used_type: ""
      });
    },
    addUsedHours() {
      let itemLocalTemp = Object.assign({}, this.itemLocal);
      itemLocalTemp.user_id = this.user.id;
      itemLocalTemp.overtimes_to_use = this.disabled
        ? "0"
        : this.dealingHours.overtimes - this.dealingHours.usedHours;

      this.$validator.validateAll().then(result => {
        if (result) {
          this.$store
            .dispatch(
              "dealingHoursManagement/addOrUpdtateUsed",
              Object.assign({}, itemLocalTemp)
            )
            .then(data => {
              if (data.data.error) {
                this.$vs.loading.close();
                this.$vs.notify({
                  time: 10000,
                  title: "Erreur",
                  text: data.data.error,
                  iconPack: "feather",
                  icon: "icon-alert-circle",
                  color: "danger"
                });
              } else {
                this.$vs.loading.close();
                this.$vs.notify({
                  title:
                    itemLocalTemp.used_hours > "01:00"
                      ? "Heures supplémentaires utilisées"
                      : "Heure supplémentaire utilisée",
                  text:
                    itemLocalTemp.used_hours > "01:00"
                      ? "Heures utilisées avec succès"
                      : "Heure utilisée avec succès",
                  iconPack: "feather",
                  icon: "icon-alert-circle",
                  color: "success"
                });
              }
            })
            .catch(error => {
              console.log(["error", error]);

              this.$vs.loading.close();
              this.$vs.notify({
                title: "Erreur",
                text: error.message,
                iconPack: "feather",
                icon: "icon-alert-circle",
                color: "danger"
              });
            });
        }
        this.clearFields();
      });
    }
  },
  created() {
    if (!moduleDealingHoursManagement.isRegistered) {
      this.$store.registerModule(
        "dealingHoursManagement",
        moduleDealingHoursManagement
      );
      moduleDealingHoursManagement.isRegistered = true;
    }
  },
  beforeDestroy() {
    moduleDealingHoursManagement.isRegistered = false;
    this.$store.unregisterModule("dealingHoursManagement");
  }
};
</script>
