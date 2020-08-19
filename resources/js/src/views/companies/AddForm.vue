<template>
  <div class="p-3 mb-4 mr-4">
    <vs-button @click="activePrompt = true" class="w-full">Ajouter une société</vs-button>
    <vs-prompt
      title="Ajouter une société"
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
                placeholder="Nom de la société"
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
              <div class="vx-row mt-4" v-if="isAdmin">
                <div class="vx-col w-full">
                  <div class="flex items-end px-3">
                    <feather-icon svgClasses="w-6 h-6" icon="LockIcon" class="mr-2" />
                    <span class="font-medium text-lg leading-none">Admin</span>
                  </div>
                  <vs-divider />
                  <div>
                    <small class="ml-1" for>Période d'essaie</small>
                    <vs-switch v-model="itemLocal.is_trial" />
                  </div>
                </div>
              </div>
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
        siret: "",
        is_trial: true
      }
    };
  },
  computed: {
    validateForm() {
      return !this.errors.any() && this.itemLocal.name !== "";
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
                title: "Ajout d'une société",
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
