<template>
  <div class="p-3 mb-4 mr-4">
    <vs-button @click="activePrompt = true" class="w-full"
      >Ajouter un client</vs-button
    >
    <vs-prompt
      title="Ajouter un client"
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
        <form autocomplete="off">
          <div class="vx-row">
            <div class="vx-col w-full">
              <v-select
                v-if="isAdmin"
                label="name"
                v-validate="'required'"
                v-model="itemLocal.company"
                :options="companiesData"
                class="w-full mb-4 mt-5"
              >
                <template #header>
                  <div class="vs-select--label">Société</div>
                </template>
              </v-select>

              <div>
                <small class="ml-1" for>Professionnel</small>
                <vs-switch v-model="itemLocal.professional" />
              </div>
              <vs-input
                v-if="itemLocal.professional"
                v-validate="itemLocal.professional ? 'required|max:50' : ''"
                name="name"
                class="w-full mb-4 mt-5"
                label="Nom de la société"
                placeholder="société..."
                v-model="itemLocal.name"
                :color="!errors.has('name') ? 'success' : 'danger'"
              />
              <span
                v-if="itemLocal.professional"
                class="text-danger text-sm"
                v-show="errors.has('name')"
                >{{ errors.first("name") }}</span
              >
              <vs-input
                v-if="itemLocal.professional"
                v-validate="
                  itemLocal.professional ? 'required|numeric|min:14|max:14' : ''
                "
                name="siret"
                class="w-full mb-4 mt-5"
                label="Numéro de siret"
                placeholder="n° siret..."
                v-model="itemLocal.siret"
              />
              <span
                v-if="itemLocal.professional"
                class="text-danger text-sm"
                v-show="errors.has('siret')"
                >{{ errors.first("siret") }}</span
              >

              <vs-input
                v-validate="'required|max:50'"
                autocomplete="off"
                name="lastname"
                class="w-full mb-4 mt-5"
                label="Nom du client"
                placeholder="nom..."
                v-model="itemLocal.lastname"
                :color="!errors.has('lastname') ? 'success' : 'danger'"
              />
              <span
                class="text-danger text-sm"
                v-show="errors.has('lastname')"
                >{{ errors.first("lastname") }}</span
              >
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
import vSelect from "vue-select";

// register custom messages
Validator.localize("fr", errorMessage);

export default {
  components: {
    vSelect,
  },
  data() {
    return {
      activePrompt: false,

      itemLocal: {
        name: null,
        lastname: null,
        company: this.isAdmin ? this.$store.state.AppActiveUser.company : null,
        siret: null,
        professional: 0,
      },
    };
  },
  computed: {
    isAdmin() {
      return this.$store.state.AppActiveUser.is_admin;
    },
    validateForm() {
      if (this.itemLocal.professional || this.itemLocal.professional === 1) {
        return (
          !this.errors.any() &&
          this.itemLocal.name !== null &&
          this.itemLocal.lastname !== null &&
          this.itemLocal.siret !== null
        );
      } else {
        return (
          !this.errors.any() &&
          this.itemLocal.lastname !== null &&
          this.itemLocal.company !== null
        );
      }
    },
    companiesData() {
      return this.$store.state.companyManagement.companies;
    },
  },
  methods: {
    clearFields() {
      Object.assign(this.itemLocal, {
        name: null,
        lastname: null,
        company: this.isAdmin ? this.$store.state.AppActiveUser.company : null,
        siret: null,
        professional: 0,
      });
    },
    addCompany() {
      this.$validator.validateAll().then((result) => {
        if (result) {
          if (
            this.itemLocal.professional ||
            this.itemLocal.professional === 1
          ) {
            this.itemLocal.professional = 1;
          } else if (
            !this.itemLocal.professional ||
            this.itemLocal.professional === 0
          ) {
            this.itemLocal.professional = 0;
            this.itemLocal.name = null;
            this.itemLocal.siret = null;
          }
          let itemLocal = Object.assign({}, this.itemLocal);
          this.$store
            .dispatch("customerManagement/addItem", itemLocal)
            .then(() => {
              this.$vs.loading.close();
              this.$vs.notify({
                title: "Ajout d'un client",
                text: `"${
                  itemLocal.name ? itemLocal.name : itemLocal.lastname
                }" ajoutée avec succès`,
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
          this.clearFields();
        }
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
};
</script>
