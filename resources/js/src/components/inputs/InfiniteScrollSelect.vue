<template>
    <v-select
        :ref="`${model}Select`"
        :label="label"
        :options="items"
        :value="value"
        :filterable="false"
        :multiple="multiple"
        :reduce="item => item.id"
        @input="onInput"
        @search:blur="onBlur"
        @search:focus="onFocus"
        @search="onSearch"
        class="w-full"
        autocomplete
    >
        <template #header>
            <slot name="header"></slot>
        </template>
        <template #selected-option>
            <div style="display: flex; align-items: baseline;">
                <span>{{ selectedItem ? selectedItem[label] : "" }}</span>
            </div>
        </template>
        <template #option="item">
            <slot name="option" :data="item">
                <span>{{ item[label] }}</span>
            </slot>
        </template>
        <template #list-footer>
            <li ref="load" class="loader" v-show="hasNextPage"></li>
        </template>
    </v-select>
</template>

<script>
import vSelect from "vue-select";
import _ from "lodash";

export default {
    components: {
        vSelect
    },
    props: {
        model: {
            type: String,
            required: true
        },
        label: {
            type: String,
            required: true
        },
        value: {
            default: null
        },
        filters: {
            type: Object,
            default: () => ({})
        },
        input: {
            type: Function,
            default: value => {}
        },
        blur: {
            type: Function,
            default: () => {}
        },
        focus: {
            type: Function,
            default: () => {}
        },
        multiple: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            observer: new IntersectionObserver(this.infiniteScroll),
            searchQuery: "",
            page: 1,
            perPage: 30,
            hasNextPage: false,

            selectedItem: null,
            items: [],

            lastSearch: {},
            fetchTimeout: null
        };
    },
    methods: {
        clearFetchTimeout() {
            if (this.fetchTimeout) {
                clearTimeout(this.fetchTimeout);
            }
        },
        onInput(value) {
            this.getItem(value);
            this.$emit("input", value);
            this.input(value);
        },
        onBlur() {
            this.observer.disconnect();
            setTimeout(this.clearFetchTimeout, 0);
            this.blur();
        },
        async onFocus() {
            await this.fetchItems();
            if (this.hasNextPage) {
                await this.$nextTick();
                this.observer.observe(this.$refs.load);
            }
            this.focus();
        },
        onSearch(query) {
            this.searchQuery = query;
            this.page = 1;
            this.clearFetchTimeout();
            this.fetchTimeout = setTimeout(this.fetchItems, 500);
        },
        async infiniteScroll([{ isIntersecting, target }]) {
            if (isIntersecting) {
                const ul = target.offsetParent;
                const scrollTop = target.offsetParent.scrollTop;
                this.page++;

                await this.fetchItems();

                await this.$nextTick();
                ul.scrollTop = scrollTop;
            }
        },
        async fetchItems() {
            const search = {
                page: this.page,
                per_page: this.perPage,
                q: this.searchQuery || undefined,
                ...this.filters
            };
            if (!_.isEqual(this.lastSearch, search)) {
                this.lastSearch = search;
                await this.$store
                    .dispatch(`${this.model}Management/fetchItems`, {
                        fields: this.label,
                        loads: "",
                        appends: "",
                        order_by: this.label,
                        ...search
                    })
                    .then(data => {
                        if (data.pagination) {
                            const { last_page } = data.pagination;
                            this.hasNextPage = this.page !== last_page;
                        }
                        if (this.page === 1) {
                            this.items = data.payload;
                        } else {
                            const ids = this.items.map(item => item.id);
                            const unique = [];
                            data.payload.forEach(item => {
                                if (ids.indexOf(item.id) === -1) {
                                    ids.push(item.id);
                                    unique.push(item);
                                }
                            });
                            this.items.push(...unique);
                        }
                    })
                    .catch(err => {
                        console.error(err);
                    });
            }
        },
        async fetchSelectedItem() {
            if (this.value) {
                const selectedId = this.value;
                if (!this.items.find(item => item.id === selectedId)) {
                    this.$store
                        .dispatch(
                            `${this.model}Management/fetchItem`,
                            selectedId
                        )
                        .then(data => {
                            this.items.unshift(data.payload);
                            this.getItem(this.value);
                        })
                        .catch(err => {
                            console.error(err);
                        });
                } else {
                    this.getItem(this.value);
                }
            }
        },
        getItem(value) {
            this.selectedItem = value
                ? (this.selectedItem = this.$store.getters[
                      `${this.model}Management/getItem`
                  ](value))
                : null;
        }
    },
    async created() {
        await this.fetchItems();
        await this.fetchSelectedItem();
    }
};
</script>

<style>
.loader {
    height: 40px;
}
</style>
