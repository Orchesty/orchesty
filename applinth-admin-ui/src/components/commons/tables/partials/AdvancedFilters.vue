<template>
  <ValidationObserver v-slot="{ validate, validated, invalid, reset }">
    <template v-for="(andItem, andIndex) in filter">
      <template v-for="(orItem, orIndex) in andItem">
        <v-row dense :key="`${andIndex}-${orIndex}`">
          <v-col cols="2">
            <SelectBox
              label="Sloupec"
              :rules="rules.column"
              :name="`column-${andIndex}-${orIndex}`"
              v-model="filter[andIndex][orIndex].column"
              hide-details
              :items="columns"
              @change="
                (selectedValue) =>
                  handleColumnChange(selectedValue, filter[andIndex][orIndex])
              "
            />
          </v-col>
          <v-col cols="2">
            <SelectBox
              label="Operátor"
              :rules="rules.operator"
              :name="`operator-${andIndex}-${orIndex}`"
              v-model="filter[andIndex][orIndex].operator"
              hide-details
              :items="selectedOperators"
              @change="
                (operator) => handleOperatorChange(andIndex, orIndex, operator)
              "
            />
          </v-col>
          <v-col cols="2" v-if="isFirstValueColumn">
            <template v-if="displayFirstValue(andIndex, orIndex)">
              <TextFieldMultiple
                label="Hodnota"
                v-if="isMultipleOperator(filter[andIndex][orIndex].operator)"
                :name="`value1-${andIndex}-${orIndex}`"
                v-model="filter[andIndex][orIndex].values"
                hide-details
              />
              <TextField
                label="Hodnota"
                v-if="
                  getTypeFromColumn(filter[andIndex][orIndex].column) ===
                  'string'
                "
                :rules="rules.value1"
                :name="`value1-${andIndex}-${orIndex}`"
                v-model="filter[andIndex][orIndex].values[0]"
                hide-details
                type="text"
              />
              <TextField
                label="Hodnota"
                v-if="
                  getTypeFromColumn(filter[andIndex][orIndex].column) ===
                  'number'
                "
                :rules="rules.value1"
                :name="`value1-${andIndex}-${orIndex}`"
                v-model="filter[andIndex][orIndex].values[0]"
                hide-details
                type="number"
              />
              <DateField
                label="Hodnota"
                v-if="
                  getTypeFromColumn(filter[andIndex][orIndex].column) ===
                  'datetime'
                "
                :rules="rules.value1"
                :name="`value1-${andIndex}-${orIndex}`"
                v-model="filter[andIndex][orIndex].values[0]"
                :all-day="isAllDayDate(filter[andIndex][orIndex].operator)"
                hide-details
              />
              <SelectField
                label="Hodnota"
                v-if="
                  getTypeFromColumn(filter[andIndex][orIndex].column) === 'enum'
                "
                :values="
                  getEnumValuesForColumn(filter[andIndex][orIndex].column)
                "
                :rules="rules.value1"
                :name="`value1-${andIndex}-${orIndex}`"
                v-model="filter[andIndex][orIndex].values[0]"
                hide-details
              />
              <SwitchField
                label="Hodnota"
                v-if="
                  getTypeFromColumn(filter[andIndex][orIndex].column) ===
                  'boolean'
                "
                :rules="{ required: false }"
                :name="`value1-${andIndex}-${orIndex}`"
                v-model="filter[andIndex][orIndex].values[0]"
                hide-details
              />
            </template>
          </v-col>
          <v-col cols="2">
            <TextField
              label="Hodnota 2"
              v-if="displaySecondValue(andIndex, orIndex, 'number')"
              :rules="rules.value2"
              :name="`value2-${andIndex}-${orIndex}`"
              v-model="filter[andIndex][orIndex].values[1]"
              hide-details
              type="number"
            />
            <DateField
              label="Hodnota 2"
              v-if="displaySecondValue(andIndex, orIndex, 'datetime')"
              :rules="rules.value2"
              :name="`value2-${andIndex}-${orIndex}`"
              v-model="filter[andIndex][orIndex].values[1]"
              :all-day="true"
              hide-details
            />
          </v-col>
          <v-spacer />
          <v-col cols="2" class="d-flex justify-end align-center">
            <Button
              outlined
              color="secondary"
              class="mr-2"
              :min-width="100"
              @click="() => handleAddOrItem(andIndex, orIndex)"
            >
              {{ $t("table.or") }}
            </Button>
            <Button
              :disabled="andItem.length === 1"
              icon
              :min-width="24"
              color="error"
              @click="() => handleItemRemove(andIndex, orIndex)"
            >
              <v-icon>delete</v-icon>
            </Button>
          </v-col>
        </v-row>
      </template>
      <v-row dense :key="`${andIndex}-and`">
        <v-col class="text-end">
          <Button
            outlined
            color="secondary"
            class="my-1"
            @click="() => handleAddAndItem(andIndex)"
          >
            {{ $t("table.and") }}
          </Button>
        </v-col>
      </v-row>
    </template>
    <v-divider class="my-3" />
    <v-row dense>
      <v-col class="text-end">
        <Button outlined color="secondary" @click="() => handleClear(reset)">{{
          $t("table.clearFilters")
        }}</Button>
        <!--        HIDDEN, UNCOMMENT IN THE CASE OF NEED FROM CLIENT-->
        <!--        <Button-->
        <!--          class="ml-2"-->
        <!--          @click="() => handleSave(validate)"-->
        <!--          :disabled="validated && invalid"-->
        <!--        >-->
        <!--          {{ $t("table.saveFilter") }}-->
        <!--        </Button>-->
        <Button
          color="secondary"
          class="ml-2"
          :disabled="validated && invalid"
          @click="() => handleFilter(validate)"
        >
          {{ $t("table.filter") }}
        </Button>
        <SaveFilterModal :table-options="tableOptions" />
      </v-col>
    </v-row>
  </ValidationObserver>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";
import {
  AdvancedFilterRules,
  EnumValues,
  TableColumnDataType,
  TableFilter,
  TableOptions,
} from "../../../../types";
import Button from "../../inputsAndControls/Button.vue";
import TextField from "../../inputsAndControls/TextField.vue";
import SelectBox from "../../inputsAndControls/SelectBox.vue";
import { ValidationObserver } from "vee-validate";
import SaveFilterModal from "./SaveFilterModal.vue";
import { userSettings } from "../../../../utils/userSettings";
import { eventBus } from "../../../../utils/eventBus";
import { EventBus } from "../../../../enums";
import TextFieldMultiple from "../../inputsAndControls/TextFieldMultiple.vue";
import store from "../../../../store";
import { TablesActions } from "../../../../store/modules/tables";
import SwitchField from "../../inputsAndControls/SwitchField.vue";
import DateField from "../../inputsAndControls/DateField.vue";
import SelectField from "../../inputsAndControls/SelectField.vue";
import { FilterOperatorEnum } from "../../../../types/gqlGeneratedPrivate";

@Component({
  components: {
    SelectField,
    DateField,
    SwitchField,
    Button,
    TextField,
    ValidationObserver,
    SaveFilterModal,
    SelectBox,
    TextFieldMultiple,
  },
})
export default class AdvancedFilter extends Vue {
  @Prop({ required: true, type: Object })
  private tableOptions!: TableOptions;

  private readonly disabledOperators = [
    FilterOperatorEnum.In,
    FilterOperatorEnum.NotIn,
    FilterOperatorEnum.Empty,
    FilterOperatorEnum.NotEmpty,
    FilterOperatorEnum.Start,
    FilterOperatorEnum.End,
  ];
  private readonly stringOperators = [
    FilterOperatorEnum.Equal,
    FilterOperatorEnum.NotEqual,
    FilterOperatorEnum.Like,
  ];
  private readonly enumOperators = [
    FilterOperatorEnum.Equal,
    FilterOperatorEnum.NotEqual,
  ];
  private readonly numberOperators = [
    FilterOperatorEnum.LowerThan,
    FilterOperatorEnum.LowerThanOrEqual,
    FilterOperatorEnum.Between,
    FilterOperatorEnum.NotBetween,
    FilterOperatorEnum.Equal,
    FilterOperatorEnum.GreaterThan,
    FilterOperatorEnum.GreaterThanOrEqual,
  ];
  private readonly booleanOperators = [FilterOperatorEnum.Like];

  filter: any = null;

  private selectedOperators: any;

  private rules: AdvancedFilterRules = {
    column: {
      required: true,
    },
    operator: {
      required: true,
    },
    value1: {
      required: true,
    },
    value2: {
      required: false,
    },
  };

  created() {
    this.filter = this.getInitFilter();
    this.selectedOperators = [];
    eventBus.$on(
      `${EventBus.OnTableQuickFilter}/${this.tableOptions.namespace}`,
      (filter: TableFilter | null) => {
        this.handleOnQuickFilter(filter);
      }
    );
  }

  get isFirstValueColumn(): boolean {
    return this.filter.some((andFilter: any) => {
      return andFilter.some((orFilter: any) => {
        return (
          orFilter.operator !== FilterOperatorEnum.Empty &&
          orFilter.operator !== FilterOperatorEnum.NotEmpty
        );
      });
    });
  }

  get isSecondValueColumn(): boolean {
    return this.filter.some((andFilter: any) => {
      return andFilter.some((orFilter: any) => {
        return (
          orFilter.operator === FilterOperatorEnum.Between ||
          orFilter.operator === FilterOperatorEnum.NotBetween
        );
      });
    });
  }

  displaySecondValue(andIndex: number, orIndex: number, comparator: string) {
    return (
      this.isRangeOperator(andIndex, orIndex) &&
      this.getTypeFromColumn(this.filter[andIndex][orIndex].column) ===
        comparator
    );
  }
  get andBtnColSpan(): number {
    if (this.isFirstValueColumn && this.isSecondValueColumn) {
      return 5;
    } else if (!this.isFirstValueColumn && !this.isSecondValueColumn) {
      return 3;
    } else {
      return 4;
    }
  }

  get columns(): { text: string; value: string }[] {
    return this.tableOptions.filterableColumns ?? [];
  }

  private isAllDayDate(operator: FilterOperatorEnum): boolean {
    switch (operator) {
      case FilterOperatorEnum.LowerThanOrEqual:
      case FilterOperatorEnum.GreaterThan:
        return true;
      default:
        return false;
    }
  }

  private getTypeFromColumn(column: string): string {
    let dataType = TableColumnDataType.String;
    const columnSettings = this.tableOptions.filterableColumns
      ?.filter((item) => item.value === column)
      .shift();

    if (columnSettings && columnSettings.type !== undefined) {
      dataType = columnSettings.type;
    }

    return dataType;
  }

  private getEnumValuesForColumn(column: string): EnumValues[] {
    let enumValues = [{ name: "", value: "" }];
    const columnSettings = this.tableOptions.filterableColumns
      ?.filter((item) => item.value === column)
      .shift();

    if (columnSettings && columnSettings.enumValues !== undefined) {
      enumValues = columnSettings.enumValues;
    }

    return enumValues;
  }

  private handleColumnChange(selectedValue: string, filterA: any) {
    const dataType = this.getTypeFromColumn(selectedValue);
    this.selectedOperators = Object.values(FilterOperatorEnum)
      .filter((operator) => {
        return !this.disabledOperators.includes(operator);
      })
      .filter((operator) => {
        switch (dataType) {
          case TableColumnDataType.Boolean:
            filterA.values = [0]; // hack for default value
            return this.booleanOperators.includes(operator);
          case TableColumnDataType.Number:
          case TableColumnDataType.DateTime:
            return this.numberOperators.includes(operator);
          case TableColumnDataType.Enum:
            return this.enumOperators.includes(operator);
          default:
            return this.stringOperators.includes(operator);
        }
      })
      .map((operator) => ({
        text: this.$t(`enum.filterOperator.${operator}`).toString(),
        value: operator,
      }));
  }

  private getInitFilter() {
    return (
      this.tableOptions.defaultFilter || [
        [
          {
            column: "",
            operator: "",
            values: [""],
          },
        ],
      ]
    );
  }

  private displayFirstValue(andIndex: number, orIndex: number): boolean {
    const operator = this.filter[andIndex][orIndex].operator;

    return !(
      operator === FilterOperatorEnum.NotEmpty ||
      operator === FilterOperatorEnum.Empty
    );
  }

  private isRangeOperator(andIndex: number, orIndex: number): boolean {
    const operator = this.filter[andIndex][orIndex].operator;

    return (
      operator === FilterOperatorEnum.Between ||
      operator === FilterOperatorEnum.NotBetween
    );
  }

  private isMultipleOperator(operator: FilterOperatorEnum): boolean {
    switch (operator) {
      case FilterOperatorEnum.In:
      case FilterOperatorEnum.NotIn:
        return true;
      default:
        return false;
    }
  }

  private changeNumberOfValues(
    andIndex: number,
    orIndex: number,
    numberOfValues: number
  ): void {
    const values: string[] = this.filter[andIndex][orIndex].values;
    switch (numberOfValues) {
      case -1:
        this.filter[andIndex][orIndex].values = values.filter((value) => value);
        break;
      case 0:
        this.filter[andIndex][orIndex].values = [];
        break;
      case 1:
        this.filter[andIndex][orIndex].values = [
          this.getFilterDefaultValue(values[0]),
        ];
        break;
      case 2:
        this.filter[andIndex][orIndex].values = [
          this.getFilterDefaultValue(values[0]),
          this.getFilterDefaultValue(values[1]),
        ];
    }
  }

  private getFilterDefaultValue(value: any): string | number {
    if (typeof value === "number" || value) {
      return value;
    }

    return "";
  }

  private handleOperatorChange(
    andIndex: number,
    orIndex: number,
    operator: FilterOperatorEnum
  ): void {
    switch (operator) {
      case FilterOperatorEnum.Between:
      case FilterOperatorEnum.NotBetween:
        this.changeNumberOfValues(andIndex, orIndex, 2);
        break;
      case FilterOperatorEnum.Empty:
      case FilterOperatorEnum.NotEmpty:
        this.changeNumberOfValues(andIndex, orIndex, 0);
        break;
      case FilterOperatorEnum.In:
      case FilterOperatorEnum.NotIn:
        this.changeNumberOfValues(andIndex, orIndex, -1);
        break;
      default:
        this.changeNumberOfValues(andIndex, orIndex, 1);
        break;
    }
  }

  private handleItemRemove(andIndex: number, orIndex: number): void {
    this.filter[andIndex].splice(orIndex, 1);
    if (!this.filter[andIndex].length) {
      this.filter.splice(andIndex, 1);
    }
    if (!this.filter.length) {
      this.filter.push([
        {
          column: "",
          operator: "",
          values: [""],
        },
      ]);
    }
  }

  private handleAddOrItem(andIndex: number, orIndex: number): void {
    this.filter[andIndex].splice(orIndex + 1, 0, {
      column: "",
      operator: "",
      values: [""],
    });
  }

  private handleAddAndItem(andIndex: number): void {
    this.filter.splice(andIndex + 1, 0, [
      {
        column: "",
        operator: "",
        values: [""],
      },
    ]);
  }

  private handleClear(reset: () => void): void {
    this.filter.splice(0, this.filter.length);
    this.filter.push([
      {
        column: "",
        operator: "",
        values: [""],
      },
    ]);
    this.$emit("filter", []);
    this.handleSaveUserSettings(null);
    reset();
  }

  private async handleSave(validate: () => Promise<boolean>): Promise<void> {
    const valid = await validate();
    if (valid) {
      eventBus.$emit(
        `${EventBus.SaveFilterModal}/${this.tableOptions.namespace}`,
        this.filter
      );
    }
  }

  private async handleFilter(validate: () => Promise<boolean>): Promise<void> {
    const valid = await validate();
    if (valid) {
      this.handleSaveUserSettings(this.filter);
      this.$emit("filter", this.filterParsed);
    }
  }

  get filterParsed() {
    return this.filter.map((innerFilter: TableFilter) => ({
      filter: innerFilter,
    }));
  }

  private handleOnQuickFilter(filter: TableFilter | null): void {
    this.filter.splice(0, this.filter.length);
    if (filter) {
      this.filter.push(...filter);
    } else {
      this.filter.push([
        {
          column: "",
          operator: "",
          values: [""],
        },
      ]);
    }
  }

  private handleSaveUserSettings(filter: TableFilter | null): void {
    if (this.tableOptions.saveTableState) {
      const lastState = userSettings.getTableLastState(
        this.tableOptions.namespace
      );
      const pager = { page: 1, size: lastState.pager?.size ?? 10 };
      userSettings.updateTableLastState(this.tableOptions.namespace, {
        ...lastState,
        activeQuickFilter: null,
        filter: filter,
        pager: pager,
      });
    }

    // Change paging when is filter changed
    store.dispatch(
      `${this.tableOptions.namespace}/${TablesActions.ResetPaging}`
    );
  }
}
</script>
