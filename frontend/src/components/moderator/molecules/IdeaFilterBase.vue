<template>
  <div class="level filter_options_base" :class="customClass">
    <div class="level-left">
      <div class="level-item">
        <el-input
          v-model="modelValue.textFilter"
          :placeholder="$t('moderator.molecule.ideaFilter.filterPlaceholder')"
          @change="change"
          clearable
        >
          <template #prefix>
            <font-awesome-icon icon="filter" />
          </template>
        </el-input>
      </div>
      <div class="level-item" v-if="useStateFilter">
        <el-select
          v-model="modelValue.stateFilter"
          class="select--fullwidth"
          multiple
          @change="change"
        >
          <template v-slot:prefix>
            <font-awesome-icon icon="filter" class="el-icon" />
          </template>
          <el-option
            v-for="state in IdeaStateKeys"
            :key="state"
            :value="state"
            :label="$t(`enum.ideaState.${IdeaStates[state]}`)"
          >
          </el-option>
        </el-select>
      </div>
      <div class="level-item">
        <el-select v-model="modelValue.orderType" @change="change">
          <template v-slot:prefix>
            <font-awesome-icon icon="sort" class="el-icon" />
          </template>
          <el-option
            v-for="type in sortOrderOptions"
            :key="type.orderType"
            :value="
              type.ref
                ? `${type.orderType}&refId=${type.ref.id}`
                : type.orderType
            "
            :label="
              $t(`enum.ideaSortOrder.${type.orderType}`) +
              (type.ref ? ` - ${type.ref.name}` : '')
            "
          >
            <span>
              {{ $t(`enum.ideaSortOrder.${type.orderType}`) }}
            </span>
            <span v-if="type.ref"> - {{ type.ref.name }} </span>
          </el-option>
        </el-select>
      </div>
      <slot name="left"></slot>
    </div>
    <div class="level-right">
      <div class="level-item link" @click="changeOrderAsc">
        <ToolTip
          :text="
            modelValue.orderAsc
              ? $t('moderator.molecule.ideaFilter.sortAscending')
              : $t('moderator.molecule.ideaFilter.sortDescending')
          "
        >
          <font-awesome-icon
            :icon="
              modelValue.orderAsc
                ? 'arrow-down-short-wide'
                : 'arrow-up-short-wide'
            "
          />
        </ToolTip>
      </div>
      <div class="level-item link" @click="collapseChanged(!collapse)">
        <ToolTip
          :text="
            collapse
              ? $t('moderator.molecule.ideaFilter.maximizeText')
              : $t('moderator.molecule.ideaFilter.minimizeText')
          "
        >
          <font-awesome-icon v-if="!collapse" icon="window-minimize" />
          <font-awesome-icon v-else icon="window-maximize" />
        </ToolTip>
      </div>
      <slot name="right"></slot>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { SortOrderOption } from '@/types/api/OrderGroup';
import IdeaSortOrder, {
  DefaultIdeaSortOrder,
} from '@/types/enum/IdeaSortOrder';
import IdeaStates from '@/types/enum/IdeaStates';
import { Prop } from 'vue-property-decorator';
import { CollapseIdeas } from '@/components/moderator/organisms/cards/IdeaCard.vue';
import ToolTip from '@/components/shared/atoms/ToolTip.vue';

export interface FilterData {
  orderType: string;
  orderAsc: boolean;
  stateFilter: string[];
  textFilter: string;
  collapseIdeas: CollapseIdeas;
}

export const defaultFilterData: FilterData = {
  orderType: DefaultIdeaSortOrder,
  orderAsc: true,
  stateFilter: Object.keys(IdeaStates),
  textFilter: '',
  collapseIdeas: CollapseIdeas.custom,
};

const ideaSortOrderOptions: SortOrderOption[] = Object.keys(IdeaSortOrder).map(
  (orderType) => {
    return { orderType: orderType.toLowerCase(), ref: null };
  }
);

@Options({
  components: { ToolTip },
  emits: ['change', 'update'],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class IdeaFilterBase extends Vue {
  @Prop({
    default: { ...defaultFilterData },
  })
  modelValue!: FilterData;
  @Prop({ default: true }) readonly useStateFilter!: boolean;
  @Prop({ default: ideaSortOrderOptions })
  readonly sortOrderOptions!: SortOrderOption[];
  @Prop({ default: '' }) readonly customClass!: string;

  IdeaStates = IdeaStates;
  IdeaStateKeys = Object.keys(IdeaStates);
  collapse = true;

  change(): void {
    this.$emit('update', this.modelValue);
    this.$emit('change', this.modelValue);
  }

  changeOrderAsc(): void {
    this.modelValue.orderAsc = !this.modelValue.orderAsc;
    this.change();
  }

  collapseChanged(collapse: boolean): void {
    this.collapse = collapse;
    if (collapse) this.modelValue.collapseIdeas = CollapseIdeas.collapseAll;
    else this.modelValue.collapseIdeas = CollapseIdeas.expandAll;
    this.change();
  }
}
</script>

<style lang="scss" scoped>
.el-input::v-deep(.el-input__prefix-inner) {
  padding: 0 0.5rem;
  margin: auto;
}

.link {
  background-color: var(--color-background);
  z-index: 10;
  padding: 0 0.5rem;
}

.filter_options_base {
  margin-bottom: 5px;
  overflow-x: auto;
  scrollbar-color: var(--color-primary) var(--color-gray);
  scrollbar-width: thin;
}

.disabled {
  color: var(--color-highlight);
}

.el-select,
.el-input {
  width: 12rem;
}

.level {
  display: flex;
  flex-wrap: wrap;
  .level-item {
    margin: 0.4rem;
  }
}

@media only screen and (max-width: 949px) {
  .level {
    justify-content: space-evenly;
    align-items: center;
    flex-shrink: unset;
  }

  .level-right {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-evenly;
    align-items: center;
    margin: 0.4rem;
  }

  .level-left {
    display: flex;
    flex-wrap: wrap;
    flex-direction: row;
    justify-content: space-evenly;
    align-items: center;
    flex-shrink: unset;
    .level-item {
      width: 45%;
    }
  }
}
</style>
