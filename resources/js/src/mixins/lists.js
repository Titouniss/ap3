export const multipleActionsMixin = {
    data() {
        return {
            selectedItems: []
        };
    },
    methods: {
        onAction() {
            if (this.gridApi) {
                this.gridApi.deselectAll();
            }
        },
        onSelectedItemsChanged() {
            if (this.gridApi) {
                this.selectedItems = this.gridApi.getSelectedRows();
            }
        }
    }
};

export const paginationMixin = {
    data() {
        const { page = 1, perPage = 10 } = this.$router.history.current.query;

        return {
            page: parseInt(page),
            perPage: parseInt(perPage),
            totalPages: parseInt(page),
            total: 0
        };
    },
    computed: {
        itemsPerPage: {
            get() {
                return this.perPage;
            },
            set(val) {
                this.perPage = val;
                this.page = 1;

                history.pushState(
                    {},
                    "",
                    `${this.$router.history.current.path}?page=${this.page}&perPage=${this.perPage}`
                );

                this.onPageChanged && this.onPageChanged();
            }
        },
        currentPage: {
            get() {
                return this.page;
            },
            set(val) {
                this.page = val;
                console.log("page", val);

                history.pushState(
                    {},
                    "",
                    `${this.$router.history.current.path}?page=${this.page}&perPage=${this.perPage}`
                );

                this.onPageChanged && this.onPageChanged();
            }
        }
    }
};
