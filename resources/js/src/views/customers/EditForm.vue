<!-- =========================================================================================
    File Name: TodoEdit.vue
    Description: Edit todo component
    ----------------------------------------------------------------------------------------
    Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
      Author: Pixinvent
    Author URL: http://www.themeforest.net/user/pixinvent
========================================================================================== -->


<template>
  <vs-prompt
    title="Edition d'un client"
    accept-text="Modifier"
    cancel-text="Annuler"
    button-cancel="border"
    @cancel="init"
    @accept="submitItem"
    @close="init"
    :is-valid="validateForm"
    :active.sync="activePrompt"
  >
    <div>
      <form>
        <div class="vx-row">
          <div class="vx-col w-full">
            <vs-input
              v-validate="'max:50|required'"
              name="lastname"
              class="w-full mb-4 mt-5"
              label="Nom du client"
              placeholder="Nom du client"
              v-model="itemLocal.lastname"
              :color="!errors.has('lastname') ? 'success' : 'danger'"
            />
            <span
              class="text-danger text-sm"
              v-show="errors.has('lastname')"
            >{{ errors.first('lastname') }}</span>
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
              placeholder="Nom de la société"
              v-model="itemLocal.name"
              :color="!errors.has('name') ? 'success' : 'danger'"
            />
            <span
              v-if="itemLocal.professional"
              class="text-danger text-sm"
              v-show="errors.has('name')"
            >{{ errors.first('name') }}</span>
            <vs-input
              v-if="itemLocal.professional"
              v-validate="itemLocal.professional ? 'required|numeric|min:14|max:14' : ''"
              name="siret"
              class="w-full mb-4 mt-5"
              label="Numéro de siret"
              placeholder="Siret"
              v-model="itemLocal.siret"
            />
            <span
              v-if="itemLocal.professional"
              class="text-danger text-sm"
              v-show="errors.has('siret')"
            >{{ errors.first('siret') }}</span>
          </div>
        </div>
      </form>
    </div>
  </vs-prompt>
</template>

<script>
import { Validator } from "vee-validate";
import errorMessage from "./errorValidForm";

// register custom messages
Validator.localize("fr", errorMessage);

export default {
  props: {
    itemId: {
      type: Number,
      required: true
    }
  },
  data() {
    return {
      itemLocal: Object.assign(
        {},
        this.$store.getters["customerManagement/getItem"](this.itemId)
      )
    };
  },
  computed: {
    activePrompt: {
      get() {
        return this.itemId && this.itemId > 0 ? true : false;
      },
      set(value) {
        this.$store
          .dispatch("customerManagement/editItem", {})
          .then(() => {})
          .catch(err => {
            console.error(err);
          });
      }
    },
    permissions() {
      return this.$store.state.roleManagement.permissions;
    },
    validateForm() {
      if (this.itemLocal.professional || this.itemLocal.professional === 1) {
        return (
          !this.errors.any() &&
          this.itemLocal.name !== "" &&
          this.itemLocal.lastname !== "" &&
          this.itemLocal.siret !== null
        );
      } else {
        return !this.errors.any() && this.itemLocal.lastname !== "";
      }
    }
  },
  methods: {
    init() {
      this.itemLocal = Object.assign(
        {},
        this.$store.getters["customerManagement/getItem"](this.itemId)
      );
    },
    submitItem() {
      if (this.itemLocal.professional || this.itemLocal.professional === 1) {
        this.itemLocal.professional = 1;
      } else if (
        !this.itemLocal.professional ||
        this.itemLocal.professional === 0
      ) {
        this.itemLocal.professional = 0;
        this.itemLocal.name = null;
        this.itemLocal.siret = null;
      }

      this.$store
        .dispatch("customerManagement/updateItem", this.itemLocal)
        .then(() => {
          this.$vs.loading.close();
          this.$vs.notify({
            title: "Modification d'un client",
            text: `"${this.itemLocal.name}" modifié avec succès`,
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
    }
  }
};
</script>