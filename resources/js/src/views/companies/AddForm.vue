<template>
  <div class="p-3 mb-4 mr-4">
    <vs-button @click="activePrompt = true" class="w-full">Ajouter une compagnie</vs-button>
    <vs-prompt
      title="Ajouter une compagnie"
      accept-text="Ajouter"
      cancel-text="Annuler"
      button-cancel="border"
      @cancel="clearFields"
      @accept="addCompany"
      @close="clearFields"
      :is-valid="validateForm"
      :active.sync="activePrompt"
    >
      <div>
        <form>
          <div class="vx-row">
            <div class="vx-col w-full">
              <vs-input
                v-validate="'max:255|required'"
                name="name"
                class="w-full mb-4 mt-5"
                placeholder="Nom de la compagnie"
                v-model="itemLocal.name"
                :color="!errors.has('name') ? 'success' : 'danger'"
              />
              <span
                class="text-danger text-sm"
                v-show="errors.has('name')"
              >{{ errors.first('name') }}</span>
              <vs-input
                v-validate="'required|numeric|min:14|max:14'"
                name="siret"
                class="w-full mb-4 mt-5"
                placeholder="Siret"
                v-model="itemLocal.siret"
              />
              <span
                class="text-danger text-sm"
                v-show="errors.has('siret')"
              >{{ errors.first('siret') }}</span>
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

// register custom messages
Validator.localize("fr", errorMessage);

export default {
  data() {
    return {
      activePrompt: false,

      itemLocal: {
        name: "",
        siret: ""
      }
    };
  },
  computed: {
    validateForm() {
      return !this.errors.any() && this.itemLocal.name !== "";
    }
  },
  methods: {
    clearFields() {
      Object.assign(this.itemLocal, {
        name: "",
        siret: ""
      });
    },
    addCompany() {
      this.$validator.validateAll().then(result => {
        if (result) {
          let itemLocal = Object.assign({}, this.itemLocal);
          this.$store
            .dispatch("companyManagement/addItem", itemLocal)
            .then(() => {
              this.$vs.loading.close();
              this.$vs.notify({
                title: "Ajout d'une compagnie",
                text: `"${itemLocal.name}" ajoutée avec succès`,
                iconPack: "feather",
                icon: "icon-alert-circle",
                color: "success"
              });
            })
            .catch(error => {
              this.$vs.loading.close();
              this.$vs.notify({
                title: "Error",
                text: error.message,
                iconPack: "feather",
                icon: "icon-alert-circle",
                color: "danger"
              });
            });
          this.clearFields();
        }
      });
    }
  }
};
</script>
