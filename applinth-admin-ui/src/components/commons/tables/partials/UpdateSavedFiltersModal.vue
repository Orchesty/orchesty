<template>
  <Modal
    v-model="isOpen"
    :cancelBtnText="$t('button.cancel')"
    :title="$t('table.updateSavedFiltersModal.title')"
  >
    <template>
      <ValidationObserver ref="form">
        <div class="list">
          <div class="item" v-for="(filter, index) in form" :key="filter.id">
            <div class="item__input">
              <TextField
                :rules="rules.name"
                v-model="form[index].name"
                hide-details
                :name="`${$t(
                  'table.updateSavedFiltersModal.form.name.name'
                )} ${index}`"
              />
            </div>
            <div class="item__actions">
              <RoundButton @click="() => handleRemove(index)" icon="close" />
            </div>
          </div>
        </div>
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
import Modal from "../../layouts/Modal.vue";
import { Component, Prop, Vue } from "vue-property-decorator";
import { eventBus } from "../../../../utils/eventBus";
import { userSettings } from "../../../../utils/userSettings";
import { EventBus } from "../../../../enums";
import { QuickFilter, TableOptions } from "../../../../types";
import TextField from "../../inputsAndControls/TextField.vue";
import RoundButton from "../../inputsAndControls/RoundButton.vue";
import { Rules } from "../../../../utils/veeValidate";
import Button from "../../inputsAndControls/Button.vue";
import deepClone from "lodash.clonedeep";
import { ValidationObserver } from "vee-validate";

type UpdateSavedFiltersInputs = "id" | "name" | "filter";
type UpdateSavedFiltersForm = { [index in UpdateSavedFiltersInputs]: any }[];
type UpdateSavedFiltersRules = { [index in UpdateSavedFiltersInputs]?: Rules };

@Component({
  components: {
    Modal,
    TextField,
    RoundButton,
    Button,
    ValidationObserver,
  },
})
export default class UpdateSavedFiltersModal extends Vue {
  @Prop({ required: true, type: Object })
  private tableOptions!: TableOptions;

  private form: UpdateSavedFiltersForm = [];

  private rules: UpdateSavedFiltersRules = {
    name: {
      required: true,
    },
  };

  private isOpen = false;

  public created() {
    eventBus.$on(
      `${EventBus.UpdateSavedFiltersModal}/${this.tableOptions.namespace}`,
      (payload: QuickFilter[]) => {
        this.form = deepClone(payload);
        this.isOpen = true;
      }
    );
  }

  private handleRemove(index: number): void {
    this.form.splice(index, 1);
  }

  private async handleSave(): Promise<void> {
    if (!this.form) return;
    const valid = await (this.$refs.form as any).validate();
    if (valid) {
      userSettings.updateTableQuickFilters(
        this.tableOptions.namespace,
        this.form
      );
    }
    this.isOpen = false;
  }
}
</script>

<style lang="scss" scoped>
.list {
  display: flex;
  flex-direction: column;

  & > *:not(:last-child) {
    margin-bottom: 0.2rem;
  }
}

.item {
  display: flex;
  align-items: center;

  & > *:not(:last-child) {
    margin-right: 1rem;
  }

  &__input {
    flex-grow: 1;
  }

  &__actions {
    flex-grow: 0;
  }
}
</style>
