<template>
  <div class="p-3 mb-4 mr-4">
    <vs-button
      type="border"
      class="items-center p-3 w-full"
      @click="activePrompt = true"
    >
      Payer des heures
    </vs-button>
    <vs-prompt
      title="Payer des heures"
      accept-text="Ajouter"
      cancel-text="Annuler"
      button-cancel="border"
      @cancel="clearFields"
      @accept="addHoursPayed"
      @close="clearFields"
      :is-valid="validateForm"
      :active.sync="activePrompt"
    >
      <div>
        <form autocomplete="off">
          <div class="vx-row">
            <div class="vx-col w-full">
              <div style="opacity: .8 font-size: .85rem">Nombre d'heures à payer</div>
            </div>
          </div>
          <div class="vx-row">
            <div class="vx-col w-full">
              <vs-input
                v-validate="{
                    required: true,                                  
                    regex : /^-?[0-9]{1,3}(?:.[0-9]{1,2})?$/,
                }"
                name="heures"
                v-model="hoursPayed"
                :color="!errors.has('heures') ? 'success' : 'danger'"
              />
              <span class="text-danger text-sm" v-show="errors.has('heures')">
              {{ errors.first("heures") }}
          </span>
            </div>
          </div>
        </form>
      </div>
    </vs-prompt>
  </div>
</template>

<script>
import { Validator } from "vee-validate";
import errorMessage from "./errorValidForm";
import { French as FrenchLocale } from "flatpickr/dist/l10n/fr.js";
import moment from "moment";

// register custom messages
Validator.localize("fr", errorMessage);

export default {
  components: {
    
  },
  props: ['id_user'],
  data() {
    return {
      activePrompt: false,
      hoursPayed: null,
      itemLocal: {
        user_id: null,
        reason: "Heures supplémentaires payées",
        starts_at: moment().endOf('month').startOf('day'),
        ends_at: moment().endOf('month').startOf('day'),
      },
    };
  },
  computed: {
    validateForm() {
      return (
        this.hoursPayed != null
      );
    },
  },
  methods: {
    clearFields() {
      Object.assign(this.itemLocal, {
        user_id: null,
        reason: "Heures supplémentaires payées",
        starts_at: moment().endOf('month').startOf('day'),
        ends_at: moment().endOf('month').startOf('day'),
      });
    },
    
    addHoursPayed() {
      console.log("itemLocal",this.itemLocal);
      this.$validator.validateAll().then((result) => {
        if (result) {
          const item = JSON.parse(JSON.stringify(this.itemLocal));
          item.starts_at = moment(item.starts_at).format("YYYY-MM-DD HH:mm");
          console.log("end month",moment().endOf('month').startOf('day').format("YYYY-MM-DD HH:mm"));
          item.ends_at = moment(item.ends_at).add(this.hoursPayed, 'hours').format("YYYY-MM-DD HH:mm");
          item.user_id = this.id_user;
          this.$store
            .dispatch("unavailabilityManagement/addItem", item)
            .then((data) => {
              this.$vs.notify({
                title: "Ajout d'heures payées",
                text: `Heures payées ajoutées avec succès`,
                iconPack: "feather",
                icon: "icon-alert-circle",
                color: "success",
              });
            })
            .catch((error) => {
              this.$vs.notify({
                title: "Erreur",
                text: error.message,
                iconPack: "feather",
                icon: "icon-alert-circle",
                color: "danger",
                time: 10000,
              });
            })
            .finally(() => {
              this.$vs.loading.close();
              this.clearFields();
            });
        }
      });
    },
  },
};
</script>
