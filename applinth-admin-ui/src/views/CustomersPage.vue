<template>
  <AppLayout>
    <div class="table-medium">
      <h1>Customers</h1>
      <div class="wrapper my-5">
        <TextField v-model="textSearch" hide-details name="" label="Search" />
        <SelectBox
          v-model="appSearch"
          hide-details
          label="Filter by application"
          :items="['asdf']"
          name="name"
        />
        <Button class="ma-auto" icon min-width="0" @click="resetFilters">
          <v-icon>close</v-icon>
        </Button>
      </div>
      <CustomersTable />
    </div>
  </AppLayout>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import AppLayout from "../components/commons/layouts/AppLayout.vue";
import Button from "../components/commons/inputsAndControls/Button.vue";
import SelectBox from "@/components/commons/inputsAndControls/SelectBox.vue";
import TextField from "@/components/commons/inputsAndControls/TextField.vue";
import CustomersTable from "@/components/app/CustomersTable.vue";

@Component({
  components: {
    CustomersTable,
    TextField,
    SelectBox,
    AppLayout,
    Button,
  },
})
export default class CustomersPage extends Vue {
  resetFilters() {
    this.textSearch = null;
    this.appSearch = null;
  }

  textSearch = null;
  appSearch = null;
}
</script>

<style lang="scss" scoped>
.wrapper {
  display: grid;
  grid-template-columns: 1fr 1fr auto;
  grid-gap: 16px;
}
</style>
