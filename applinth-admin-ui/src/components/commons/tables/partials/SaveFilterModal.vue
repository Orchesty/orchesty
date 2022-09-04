<template>
  <Modal
    :max-width="450"
    :title="$t('table.saveFilterModal.title')"
    :cancelBtnText="$t('button.cancel')"
    :onCancel="resetForm"
    v-model="isOpen"
  >
    <template>
      <ValidationObserver ref="form">
        <TextField
          :label="$t('table.saveFilterModal.form.name.label')"
          :name="$t('table.saveFilterModal.form.name.name')"
          :rules="rules.name"
          v-model="form.name"
          autofocus
        />
      </ValidationObserver>
    </template>
    <template slot="actions-right">
      <Button text color="primary" @click="handleSave">
        {{ $t("button.save") }}
      </Button>
    </template>
  </Modal>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";
import { EventBus } from "../../../../enums";
import { userSettings } from "../../../../utils/userSettings";
import { eventBus } from "../../../../utils/eventBus";
import Modal from "../../layouts/Modal.vue";
import { TableFilter, TableOptions } from "../types";
import TextField from "../../inputsAndControls/TextField.vue";
import Button from "../../inputsAndControls/Button.vue";
import { ValidationObserver } from "vee-validate";
import { Rules } from "../../../../utils/veeValidate";
import { TableSettings } from "../../../../types";

type SaveFilterInputs = "name";
type SaveFilterForm = { [index in SaveFilterInputs]: any };
type SaveFilterRules = { [index in SaveFilterInputs]?: Rules };

@Component({
  components: {
    Modal,
    TextField,
    Button,
    ValidationObserver,
  },
})
export default class SaveFilterModal extends Vue {
  @Prop({ required: true, type: Object })
  private tableOptions!: TableOptions;

  private filter?: TableFilter;

  private isOpen = false;

  private form: SaveFilterForm = {
    name: "",
  };

  private rules: SaveFilterRules = {
    name: {
      required: true,
    },
  };

  public created(): void {
    eventBus.$on(
      `${EventBus.SaveFilterModal}/${this.tableOptions.namespace}`,
      (payload: TableFilter) => {
        this.filter = payload;
        this.isOpen = true;
      }
    );
  }

  private async handleSave(): Promise<void> {
    if (!this.filter) return;
    const valid = await (this.$refs.form as any).validate();
    if (valid) {
      const id = Date.now();
      const newQuickFilters: TableSettings["quickFilters"] = [
        ...userSettings.getTableQuickFilters(this.tableOptions.namespace),
        {
          id,
          name: this.form.name,
          filter: this.filter,
        },
      ];
      userSettings.updateTableQuickFilters(
        this.tableOptions.namespace,
        newQuickFilters,
        id
      );
      this.resetForm();
      this.isOpen = false;
    }
  }

  private resetForm(): void {
    (this.$refs.form as any).reset();
    this.form = {
      name: "",
    };
  }
}
</script>

<style lang="scss" scoped></style>
