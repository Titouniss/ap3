<template>
  <vs-prompt
    title="Edition"
    accept-text="Modifier"
    cancel-text="Annuler"
    button-cancel="border"
    @cancel="init"
    @accept="submitTodo"
    @close="init"
    :is-valid="validateForm"
    :active.sync="activePrompt"
  >
    <div>
      <form>
        <div class="vx-row">
          <div class="vx-col w-full">
            <vs-input
              v-validate="'required|alpha_dash|min:3'"
              data-vv-validate-on="blur"
              label-placeholder="Nom"
              name="lastname"
              placeholder="Nom"
              v-model="itemLocal.lastname"
              class="w-full mt-8"
            />
            <span class="text-danger text-sm">{{ errors.first('lastname') }}</span>

            <vs-input
              v-validate="'required|alpha_dash|min:3'"
              data-vv-validate-on="blur"
              label-placeholder="Prénom"
              name="firstname"
              placeholder="Prénom"
              v-model="itemLocal.firstname"
              class="w-full mt-8"
            />
            <span class="text-danger text-sm">{{ errors.first('firstname') }}</span>

            <div class="mt-4">
              <label class="text-sm">Sexe</label>
              <div class="mt-2">
                <vs-radio
                  v-model="itemLocal.genre"
                  vs-value="H"
                  color="primary"
                  vs-name="radio_genre"
                  class="mr-4"
                >Homme</vs-radio>
                <vs-radio
                  v-model="itemLocal.genre"
                  vs-value="F"
                  color="primary"
                  vs-name="radio_genre"
                  class="mr-4"
                >Femme</vs-radio>
              </div>
            </div>

            <vs-input
              v-validate="'min:10|max:10|required'"
              data-vv-validate-on="blur"
              name="phone_number"
              type="phone_number"
              label-placeholder="Numéro de téléphone"
              placeholder="Numéro de téléphone"
              v-model="itemLocal.phone_number"
              class="w-full mt-8"
            />
            <span class="text-danger text-sm">{{ errors.first('phone_number') }}</span>

            <vs-input
              v-validate="'required|email'"
              data-vv-validate-on="blur"
              label-placeholder="Email"
              name="email"
              placeholder="Email"
              v-model="itemLocal.email"
              class="w-full mt-8"
            />
            <span class="text-danger text-sm">{{ errors.first('email') }}</span>

            <div>
              <vs-select
                v-validate="'required'"
                name="company_id"
                label="Société"
                v-model="itemLocal.company_id"
                class="w-full mt-5"
              >
                <vs-select-item
                  :key="index"
                  :value="item.id"
                  :text="item.name"
                  v-for="(item,index) in companies"
                />
              </vs-select>
              <span
                class="text-danger text-sm"
                v-show="errors.has('company_id')"
              >{{ errors.first('company_id') }}</span>
            </div>

            <vs-select label="Rôle" v-model="selected" class="w-full mt-5">
              <vs-select-item
                v-validate="'required'"
                :key="index"
                :value="item.id"
                :text="item.name"
                v-for="(item,index) in roles"
              />
            </vs-select>
          </div>
        </div>
      </form>
    </div>
  </vs-prompt>
</template>

<script>
// Store Module
import moduleRoleManagement from "@/store/role-management/moduleRoleManagement.js";
import moduleCompanyManagement from "@/store/company-management/moduleCompanyManagement.js";

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
        this.$store.getters["userManagement/getItem"](this.itemId)
      ),
      selected: this.$store.getters["userManagement/getRole"](this.itemId)
    };
  },
  computed: {
    activePrompt: {
      get() {
        return this.itemId && this.itemId > 0 ? true : false;
      },
      set(value) {
        this.$store
          .dispatch("userManagement/editItem", {})
          .then(() => {})
          .catch(err => {
            console.error(err);
          });
      }
    },
    companies() {
      return this.$store.state.companyManagement.companies;
    },
    roles() {
      return this.$store.getters["roleManagement/getItems"];
    },
    validateForm() {
      return (
        !this.errors.any() &&
        this.itemLocal.firstname !== "" &&
        this.itemLocal.lastname !== "" &&
        (this.itemLocal.genre === "H" || this.itemLocal.genre === "F") &&
        this.itemLocal.phone_number !== "" &&
        this.itemLocal.email !== "" &&
        this.itemLocal.roles !== null
      );
    }
  },
  methods: {
    init() {
      this.itemLocal = Object.assign(
        {},
        this.$store.getters["userManagement/getItem"](this.itemId)
      );
    },
    submitTodo() {
      this.itemLocal.roles = [
        this.$store.getters["roleManagement/getItem"](this.selected)
      ];
      console.log(["ici", this.itemLocal.roles]);

      this.$store.dispatch("userManagement/updateItem", this.itemLocal);
      this.usersData;
    }
  },
  created() {
    if (!moduleRoleManagement.isRegistered) {
      this.$store.registerModule("roleManagement", moduleRoleManagement);
      moduleRoleManagement.isRegistered = true;
    }
    if (!moduleCompanyManagement.isRegistered) {
      this.$store.registerModule("companyManagement", moduleCompanyManagement);
      moduleCompanyManagement.isRegistered = true;
    }
    this.$store.dispatch("roleManagement/fetchItems").catch(err => {
      console.error(err);
    });
    if (this.$store.getters.userHasPermissionTo("read companies")) {
      this.$store.dispatch("companyManagement/fetchItems").catch(err => {
        console.error(err);
      });
    }
  }
};
</script>
