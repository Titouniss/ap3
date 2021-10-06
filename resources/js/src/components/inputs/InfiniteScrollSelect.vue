<template>
    <vs-select
        :ref="`${model}Select`"
        :value="value"
        :label="label"
        :multiple="multiple"
        :loading="loading"
        autocomplete
        @input="onInput"
        @change="onChange"
        @blur="onBlur"
        @focus="onFocus"
        @input-change="onSearch"
        class="w-full"
    >
        <vs-select-item
            v-for="item in items"
            :key="item.id"
            :value="item.id"
            :text="itemText(item)"
        />

        <li ref="load" class="loader" v-show="hasNextPage"></li>
    </vs-select>
</template>

<script>
import _ from "lodash";

export default {
    props: {
        label: {
            type: String,
            default: null
        },
        model: {
            type: String,
            required: true
        },
        itemLabel: {
            type: String,
            required: true
        },
        itemText: {
            type: Function,
            default(item) {
                return item[this.itemLabel];
            }
        },
        itemFields: {
            type: Array,
            default() {
                return [this.itemLabel];
            }
        },
        value: {
            default: null
        },
        filters: {
            type: Object,
            default: () => ({})
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
            fetchTimeout: null,

            loading: false
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
        },
        onChange(value) {
            if (!Number.isInteger(value)) this.onInput(null);
        },
        onBlur() {
            this.observer.disconnect();
            setTimeout(this.clearFetchTimeout, 0);
            this.$emit("blur");
        },
        async onFocus() {
            await this.fetchItems();
            if (this.hasNextPage) {
                await this.$nextTick();
                this.observer.observe(this.$refs.load);
            }
            this.$emit("focus");
        },
        onSearch(event) {
            this.searchQuery = event.target.value;
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
                this.loading = true;
                await this.$store
                    .dispatch(`${this.model}Management/fetchItems`, {
                        fields: this.itemFields,
                        loads: "",
                        appends: "",
                        order_by: this.itemLabel,
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
                    })
                    .finally(() => {
                        this.loading = false;
                        this.$refs[`${this.model}Select`].filterItems(
                            this.searchQuery
                        );
                        this.$refs[`${this.model}Select`].$children.map(
                            item => {
                                item.valueInputx = this.searchQuery;
                            }
                        );
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
