<template>
  <div class="the-navbar__user-meta flex items-center" v-if="activeUserInfo">
    <div class="text-right leading-tight hidden sm:block">
      <p class="font-semibold">{{ displayName }}</p>
      <small>{{company}}</small>
    </div>

    <vs-dropdown vs-custom-content vs-trigger-click class="cursor-pointer">
      <div class="con-img ml-3">
        <img
          v-if="activeUserInfo.photoURL"
          key="onlineImg"
          :src="activeUserInfo.photoURL"
          alt="user-img"
          width="40"
          height="40"
          class="rounded-full shadow-md cursor-pointer block"
        />
        <vs-avatar v-if="!activeUserInfo.photoURL" size="large" :text="displayName" />
      </div>

      <vs-dropdown-menu class="vx-navbar-dropdown">
        <ul style="min-width: 9rem">
          <li
            class="flex py-2 px-4 cursor-pointer hover:bg-primary hover:text-white"
            @click="profil"
          >
            <feather-icon icon="UserIcon" svgClasses="w-4 h-4" />
            <span class="ml-2">Profil</span>
          </li>

          <vs-divider class="m-1" />

          <li
            class="flex py-2 px-4 cursor-pointer hover:bg-primary hover:text-white"
            @click="logout"
          >
            <feather-icon icon="LogOutIcon" svgClasses="w-4 h-4" />
            <span class="ml-2">Déconnexion</span>
          </li>
        </ul>
      </vs-dropdown-menu>
    </vs-dropdown>
  </div>
</template>

<script>
export default {
  data() {
    return {
      company: null,
      userId: null,
      displayName: ""
    };
  },
  computed: {
    activeLink() {
      return !!(
        (this.to === this.$route.path ||
          this.$route.meta.parent === this.slug) &&
        this.to
      );
    },
    activeUserInfo() {
      const user = this.$store.state.AppActiveUser;

      if (user && user.id !== null) {
        const lastname =
          user.lastname && user.lastname !== null
            ? user.lastname.toUpperCase()
            : "";
        this.displayName = user.firstname + " " + lastname;
        this.userId = user.id;
        if (user.company) {
          this.company = user.company.name;
        }
      } else {
        this.$store
          .dispatch("auth/logoutJWT", localStorage.getItem("token"))
          .then(() => {
            this.$router.push("/pages/login").catch(() => {});
          })
          .catch(error => {
            this.$router.push("/pages/login").catch(() => {});
          });
      }
      return user;
    }
  },
  methods: {
    logout() {
      this.$vs.loading();
      this.$store
        .dispatch("auth/logoutJWT", localStorage.getItem("token"))
        .then(() => {
          this.$vs.loading.close();
          this.$router.push("/pages/login").catch(() => {});
        })
        .catch(error => {
          this.$vs.loading.close();
          this.$vs.notify({
            title: "Echec",
            text: error.message,
            iconPack: "feather",
            icon: "icon-alert-circle",
            color: "danger"
          });
        });
    },
    profil() {
      this.$router
        .push("/users/user-profil-edit/" + this.userId)
        .catch(() => {});
    }
  }
};
</script>
