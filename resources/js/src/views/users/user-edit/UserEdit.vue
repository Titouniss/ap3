<!-- =========================================================================================
  File Name: UserEdit.vue
  Description: User Edit Page
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
  Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
========================================================================================== -->

<template>
  <div id="page-user-edit">
    <vs-alert
      color="danger"
      title="User Not Found"
      :active.sync="user_not_found"
    >
      <span>User record with id: {{ $route.params.userId }} not found.</span>
      <span>
        <span>Check</span>
        <router-link
          :to="{ name: 'page-user-list' }"
          class="text-inherit underline"
          >All Users</router-link
        >
      </span>
    </vs-alert>

    <vx-card v-if="user_data">
      <div slot="no-body" class="tabs-container px-6 pt-6">
        <vs-tabs v-model="activeTab" class="tab-action-btn-fill-conatiner">
          <vs-tab label="Compte" icon-pack="feather" icon="icon-user">
            <div class="tab-text">
              <user-edit-tab-account class="mt-4" :data="user_data" />
            </div>
          </vs-tab>
          <vs-tab
            label="Indisponibilités"
            icon-pack="feather"
            icon="icon-clock"
          >
            <div class="tab-text">
              <UnavailabilitiesIndex class="mt-4" :data="user_data" />
            </div>
          </vs-tab>
          <vs-tab
            label="Notifications"
            icon-pack="feather"
            icon="icon-alert-triangle"
          >
            <div class="tab-text">
              <user-edit-tab-notifications class="mt-4" :data="user_data" />
            </div>
          </vs-tab>
          <vs-tab label="Mot de passe" icon-pack="feather" icon="icon-lock">
            <div class="tab-text">
              <user-edit-tab-password class="mt-4" :data="user_data" />
            </div>
          </vs-tab>
        </vs-tabs>
      </div>
    </vx-card>
  </div>
</template>

<script>
import { Validator } from "vee-validate";
import errorMessage from "./errorValidForm";

// register custom messages
Validator.localize("fr", errorMessage);

import UserEditTabAccount from "./UserEditTabAccount.vue";
import UserEditTabNotifications from "./UserEditTabNotifications.vue";
import UserEditTabPassword from "./UserEditTabPassword.vue";
import UnavailabilitiesIndex from "../../unavailabilities/Index.vue";

// Store Module
import moduleUserManagement from "@/store/user-management/moduleUserManagement.js";

export default {
  components: {
    UserEditTabAccount,
    UnavailabilitiesIndex,
    UserEditTabNotifications,
    UserEditTabPassword,
  },
  data() {
    return {
      user_data: null,
      user_not_found: false,

      /*
        This property is created for fetching latest data from API when tab is changed

        Please check it's watcher
      */
      activeTab: this.$route.query.tab ? this.$route.query.tab : 0,
    };
  },
  watch: {
    activeTab() {
      this.fetch_user_data(this.$route.params.userId);
    },
  },
  methods: {
    fetch_user_data(userId) {
      this.$store
        .dispatch("userManagement/fetchItem", userId)
        .then((data) => {
          this.user_data = data.payload;
        })
        .catch((err) => {
          if (err.response.status === 404) {
            this.user_not_found = true;
            return;
          }
          console.error(err);
        });
    },
  },
  created() {
    console.log("this.$route.query", this.$route.query);

    // Register Module UserManagement Module
    if (!moduleUserManagement.isRegistered) {
      this.$store.registerModule("userManagement", moduleUserManagement);
      moduleUserManagement.isRegistered = true;
    }
    this.fetch_user_data(this.$route.params.userId);
  },
};
</script>
