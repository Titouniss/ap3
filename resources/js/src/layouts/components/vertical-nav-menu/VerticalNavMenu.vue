<!-- =========================================================================================
  File Name: VerticalNavMenu.vue
  Description: Vertical NavMenu Component
  Component Name: VerticalNavMenu
  ----------------------------------------------------------------------------------------
  Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
    Author: Pixinvent
  Author URL: http://www.themeforest.net/user/pixinvent
========================================================================================== -->

<template>
    <div class="parentx">
        <vs-sidebar
            class="v-nav-menu items-no-padding"
            v-model="isVerticalNavMenuActive"
            ref="verticalNavMenu"
            default-index="-1"
            :click-not-close="clickNotClose"
            :reduce-not-rebound="reduceNotRebound"
            :parent="parent"
            :hiddenBackground="clickNotClose"
            :reduce="reduce"
            v-hammer:swipe.left="onSwipeLeft"
        >
            <div @mouseenter="mouseEnter" @mouseleave="mouseLeave">
                <!-- Header -->
                <div
                    class="header-sidebar flex items-end justify-between"
                    slot="header"
                >
                    <!-- Logo -->
                    <router-link
                        tag="div"
                        class="vx-logo cursor-pointer flex items-center"
                        to="/"
                    >
                        <logo
                            class="w-10 fill-current text-primary"
                            v-show="reduce && !isMouseEnter"
                        />
                        <img
                            v-show="!reduce || isMouseEnter"
                            src="@assets/images/logo/logo.png"
                            alt="Logo"
                            class="vx-logo-full"
                        />
                    </router-link>
                    <!-- /Logo -->

                    <!-- Menu Buttons -->
                    <div>
                        <!-- Close Button -->
                        <template v-if="showCloseButton">
                            <feather-icon
                                icon="XIcon"
                                class="m-0 cursor-pointer"
                                @click="
                                    $store.commit(
                                        'TOGGLE_IS_VERTICAL_NAV_MENU_ACTIVE',
                                        false
                                    )
                                "
                            />
                        </template>

                        <!-- Toggle Buttons -->
                        <template
                            v-else-if="
                                !showCloseButton && !verticalNavMenuItemsMin
                            "
                        >
                            <feather-icon
                                id="btnVNavMenuMinToggler"
                                class="mr-0 cursor-pointer"
                                :icon="reduce ? 'CircleIcon' : 'DiscIcon'"
                                svg-classes="stroke-current text-primary"
                                @click="toggleReduce(!reduce)"
                            />
                        </template>
                    </div>
                    <!-- /Menu Toggle Buttons -->
                </div>
                <!-- /Header -->

                <!-- Header Shadow -->
                <div class="shadow-bottom" v-show="showShadowBottom" />

                <!-- Menu Items -->
                <component
                    :is="scrollbarTag"
                    ref="verticalNavMenuPs"
                    class="scroll-area-v-nav-menu pt-2 flex flex-col"
                    :settings="settings"
                    @ps-scroll-y="psSectionScroll"
                    :key="$vs.rtl"
                >
                    <template v-for="(item, index) in menuItemsUpdated">
                        <!-- Group Header -->
                        <span
                            v-if="item.header && !verticalNavMenuItemsMin"
                            class="navigation-header truncate"
                            :key="`header-${index}`"
                            >{{ item.header }}</span
                        >
                        <!-- /Group Header -->

                        <template v-else-if="!item.header && item.show">
                            <!-- Nav-Item -->
                            <v-nav-menu-item
                                v-if="!item.submenu"
                                :key="`item-${index}`"
                                :index="index"
                                :to="
                                    item.slug !== 'external' &&
                                    !isAdmin &&
                                    item.name == 'Sociétés'
                                        ? '/companies/company-edit/' + companyId
                                        : item.slug !== 'external' &&
                                          !isAdmin &&
                                          item.name == 'Remontées de bugs'
                                        ? '/bugs/bug-add/'
                                        : (item.slug !== 'external' &&
                                              item.name ==
                                                  'Gérer mes heures') ||
                                          item.name == 'Gérer les heures'
                                        ? '/hours/hours-view/'
                                        : (item.slug !== 'external' &&
                                              item.name ==
                                                  'Gérer mes indisponibilités') ||
                                          item.name ==
                                              'Gérer les indisponibilités'
                                        ? '/unavailabilities/'
                                        : item.slug !== 'external'
                                        ? item.url
                                        : null
                                "
                                :href="
                                    item.slug === 'external' ? item.url : null
                                "
                                :icon="item.icon"
                                :target="item.target"
                                :isDisabled="item.isDisabled"
                                :slug="item.slug"
                            >
                                <span
                                    v-show="!verticalNavMenuItemsMin"
                                    class="truncate"
                                >
                                    {{
                                        !isAdmin && item.name == "Sociétés"
                                            ? "Ma société"
                                            : !isAdmin &&
                                              item.name == "Remontées de bugs"
                                            ? "Remonter un bug"
                                            : !isAdmin &&
                                              !isManager &&
                                              item.name == "Gérer les heures"
                                            ? "Gérer mes heures"
                                            : !isAdmin &&
                                              !isManager &&
                                              item.name ==
                                                  "Gérer les indisponibilités"
                                            ? "Gérer mes indisponibilités"
                                            : item.name
                                    }}
                                </span>
                                <vs-chip
                                    class="ml-auto"
                                    :color="item.tagColor"
                                    v-if="item.tag && (isMouseEnter || !reduce)"
                                >
                                    {{ item.tag }}
                                </vs-chip>
                            </v-nav-menu-item>

                            <!-- Nav-Group -->
                            <template v-else>
                                <v-nav-menu-group
                                    :key="`group-${index}`"
                                    :openHover="openGroupHover"
                                    :group="item"
                                    :groupIndex="index"
                                    :open="isGroupActive(item)"
                                />
                            </template>

                            <!-- /Nav-Group -->
                        </template>
                    </template>
                    <div class="flex flex-auto"></div>

                    <v-nav-menu-item
                        href="/storage/Plannigo_V0.apk"
                        icon="DownloadIcon"
                    >
                        <span
                            v-show="!verticalNavMenuItemsMin"
                            class="truncate"
                        >
                            Télécharger l'app
                        </span>
                    </v-nav-menu-item>
                </component>
                <!-- /Menu Items -->
            </div>
        </vs-sidebar>

        <!-- Swipe Gesture -->
        <div
            v-if="!isVerticalNavMenuActive"
            class="v-nav-menu-swipe-area"
            v-hammer:swipe.right="onSwipeAreaSwipeRight"
        />
        <!-- /Swipe Gesture -->
    </div>
</template>

<script>
import VuePerfectScrollbar from "vue-perfect-scrollbar";
import VNavMenuGroup from "./VerticalNavMenuGroup.vue";
import VNavMenuItem from "./VerticalNavMenuItem.vue";

import Logo from "../Logo.vue";

export default {
    name: "v-nav-menu",
    components: {
        VNavMenuGroup,
        VNavMenuItem,
        VuePerfectScrollbar,
        Logo
    },
    props: {
        logo: { type: String },
        openGroupHover: { type: Boolean, default: false },
        parent: { type: String },
        reduceNotRebound: { type: Boolean, default: true },
        navMenuItems: { type: Array, required: true },
        title: { type: String }
    },
    data: () => ({
        clickNotClose: false, // disable close navMenu on outside click
        isMouseEnter: false,
        reduce: false, // determines if navMenu is reduce - component property
        showCloseButton: false, // show close button in smaller devices
        settings: {
            // perfectScrollbar settings
            maxScrollbarLength: 60,
            wheelSpeed: 1,
            swipeEasing: true
        },
        showShadowBottom: false
    }),
    computed: {
        isAdmin() {
            return this.$store.state.AppActiveUser.is_admin;
        },
        isManager() {
            return this.$store.state.AppActiveUser.is_manager;
        },
        companyId() {
            return this.$store.state.AppActiveUser.company_id;
        },
        isGroupActive() {
            return item => {
                const path = this.$route.fullPath;
                const routeParent = this.$route.meta
                    ? this.$route.meta.parent
                    : undefined;
                let open = false;

                const func = item => {
                    if (item.submenu) {
                        item.submenu.forEach(item => {
                            if (
                                item.url &&
                                (path === item.url || routeParent === item.slug)
                            ) {
                                open = true;
                            } else if (item.submenu) {
                                func(item);
                            }
                        });
                    }
                };
                func(item);
                return open;
            };
        },
        menuItemsUpdated() {
            const clone = this.navMenuItems.slice();
            const user = this.$store.state.AppActiveUser;
            let userPermissions = this.$store.getters.AppActiveUserPermissions;
            for (const [index, item] of this.navMenuItems.entries()) {
                if (item.header && item.items.length && (index || 1)) {
                    const i = clone.findIndex(ix => ix.header === item.header);
                    for (const [subIndex, subItem] of item.items.entries()) {
                        clone.splice(i + 1 + subIndex, 0, subItem);
                    }
                }
                if (user && user.id !== null) {
                    if (this.isAdmin || item.slug === "home") {
                        item.show = true;
                    } else {
                        item.show = item.show = this.$store.getters.userHasPermissionTo(
                            `show ${item.slug}`
                        );
                    }
                }
            }

            return clone;
        },
        isVerticalNavMenuActive: {
            get() {
                return this.$store.state.isVerticalNavMenuActive;
            },
            set(val) {
                this.$store.commit("TOGGLE_IS_VERTICAL_NAV_MENU_ACTIVE", val);
            }
        },
        layoutType() {
            return this.$store.state.mainLayoutType;
        },
        reduceButton: {
            get() {
                return this.$store.state.reduceButton;
            },
            set(val) {
                this.$store.commit("TOGGLE_REDUCE_BUTTON", val);
            }
        },
        isVerticalNavMenuReduced() {
            return Boolean(this.reduce && this.reduceButton);
        },
        verticalNavMenuItemsMin() {
            return this.$store.state.verticalNavMenuItemsMin;
        },
        scrollbarTag() {
            return this.$store.state.is_touch_device
                ? "div"
                : "VuePerfectScrollbar";
        },
        windowWidth() {
            return this.$store.state.windowWidth;
        }
    },
    watch: {
        $route() {
            if (this.isVerticalNavMenuActive && this.showCloseButton)
                this.$store.commit("TOGGLE_IS_VERTICAL_NAV_MENU_ACTIVE", false);
        },
        reduce(val) {
            const verticalNavMenuWidth = val
                ? "reduced"
                : this.$store.state.windowWidth < 1200
                ? "no-nav-menu"
                : "default";
            this.$store.dispatch(
                "updateVerticalNavMenuWidth",
                verticalNavMenuWidth
            );

            setTimeout(function() {
                window.dispatchEvent(new Event("resize"));
            }, 100);
        },
        layoutType() {
            this.setVerticalNavMenuWidth();
        },
        reduceButton() {
            this.setVerticalNavMenuWidth();
        },
        windowWidth() {
            this.setVerticalNavMenuWidth();
        }
    },
    methods: {
        // handleWindowResize(event) {
        //   this.windowWidth = event.currentTarget.innerWidth;
        //   this.setVerticalNavMenuWidth()
        // },
        onSwipeLeft() {
            if (this.isVerticalNavMenuActive && this.showCloseButton)
                this.isVerticalNavMenuActive = false;
        },
        onSwipeAreaSwipeRight() {
            if (!this.isVerticalNavMenuActive && this.showCloseButton)
                this.isVerticalNavMenuActive = true;
        },
        psSectionScroll() {
            this.showShadowBottom =
                this.$refs.verticalNavMenuPs.$el.scrollTop > 0;
        },
        mouseEnter() {
            if (this.reduce)
                this.$store.commit("UPDATE_VERTICAL_NAV_MENU_ITEMS_MIN", false);
            this.isMouseEnter = true;
        },
        mouseLeave() {
            if (this.reduce)
                this.$store.commit("UPDATE_VERTICAL_NAV_MENU_ITEMS_MIN", true);
            this.isMouseEnter = false;
        },
        setVerticalNavMenuWidth() {
            if (this.windowWidth > 1200) {
                if (this.layoutType === "vertical") {
                    // Set reduce
                    this.reduce = !!this.reduceButton;

                    // Open NavMenu
                    this.$store.commit(
                        "TOGGLE_IS_VERTICAL_NAV_MENU_ACTIVE",
                        true
                    );

                    // Set Menu Items Only Icon Mode
                    const verticalNavMenuItemsMin = !!(
                        this.reduceButton && !this.isMouseEnter
                    );
                    this.$store.commit(
                        "UPDATE_VERTICAL_NAV_MENU_ITEMS_MIN",
                        verticalNavMenuItemsMin
                    );

                    // Menu Action buttons
                    this.clickNotClose = true;
                    this.showCloseButton = false;

                    const verticalNavMenuWidth = this.isVerticalNavMenuReduced
                        ? "reduced"
                        : "default";
                    this.$store.dispatch(
                        "updateVerticalNavMenuWidth",
                        verticalNavMenuWidth
                    );

                    return;
                }
            }

            // Close NavMenu
            this.$store.commit("TOGGLE_IS_VERTICAL_NAV_MENU_ACTIVE", false);

            // Reduce button
            if (this.reduceButton) this.reduce = false;

            // Menu Action buttons
            this.showCloseButton = true;
            this.clickNotClose = false;

            // Update NavMenu Width
            this.$store.dispatch("updateVerticalNavMenuWidth", "no-nav-menu");

            // Remove Only Icon in Menu
            this.$store.commit("UPDATE_VERTICAL_NAV_MENU_ITEMS_MIN", false);

            // if(this.layoutType === 'vertical' || (this.layoutType === 'horizontal' && this.windowWidth < 1200))
            // if (this.windowWidth < 1200) {

            //   // Close NavMenu
            //   this.$store.commit('TOGGLE_IS_VERTICAL_NAV_MENU_ACTIVE', false)

            //   // Reduce button
            //   if (this.reduceButton) this.reduce = false

            //   // Menu Action buttons
            //   this.showCloseButton = true
            //   this.clickNotClose   = false

            //   // Update NavMenu Width
            //   this.$store.dispatch('updateVerticalNavMenuWidth', 'no-nav-menu')

            //   // Remove Only Icon in Menu
            //   this.$store.commit('UPDATE_VERTICAL_NAV_MENU_ITEMS_MIN', false)

            // } else {

            //   // Set reduce
            //   this.reduce = this.reduceButton ? true : false

            //   // Open NavMenu
            //   this.$store.commit('TOGGLE_IS_VERTICAL_NAV_MENU_ACTIVE', true)

            //   // Set Menu Items Only Icon Mode
            //   const verticalNavMenuItemsMin = (this.reduceButton && !this.isMouseEnter) ? true : false
            //   this.$store.commit('UPDATE_VERTICAL_NAV_MENU_ITEMS_MIN', verticalNavMenuItemsMin)

            //   // Menu Action buttons
            //   this.clickNotClose   = true
            //   this.showCloseButton = false

            //   const verticalNavMenuWidth   = this.isVerticalNavMenuReduced ? "reduced" : "default"
            //   this.$store.dispatch('updateVerticalNavMenuWidth', verticalNavMenuWidth)
            // }
        },
        toggleReduce(val) {
            this.reduceButton = val;
            this.setVerticalNavMenuWidth();
        }
    },
    mounted() {
        this.setVerticalNavMenuWidth();
    }
};
</script>

<style lang="scss">
@import "@sass/vuexy/components/verticalNavMenu.scss";

.vx-logo-full {
    height: 2.9rem;
    margin-left: 5px;
    margin-bottom: -0.5rem;
}
</style>
