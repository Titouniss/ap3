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
