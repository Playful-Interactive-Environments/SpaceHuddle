<template>
  <div class="level filter_options">
    <div class="level-right">
      <div class="level-item">
        <el-input
          v-model="modelValue.textFilter"
          :placeholder="
            $t('moderator.molecule.sessionFilter.filterPlaceholder')
          "
          clearable
        >
          <template #prefix>
            <font-awesome-icon icon="filter" />
          </template>
        </el-input>
      </div>
      <div class="level-item">
        <el-select v-model="modelValue.orderType">
          <template v-slot:prefix>
            <font-awesome-icon icon="sort" class="el-icon" />
          </template>
          <el-option
            v-for="type in sessionSortOrderOptions"
            :key="type.orderType"
            :value="type.orderType"
            :label="$t(`enum.sessionSortOrder.${type.orderType}`)"
          >
            <span>
              {{ $t(`enum.sessionSortOrder.${type.orderType}`) }}
            </span>
          </el-option>
        </el-select>
      </div>
      <div class="level-item">
        <div class="link" @click="changeOrderAsc">
          <font-awesome-icon
            :icon="
              modelValue.orderAsc
                ? 'arrow-down-short-wide'
                : 'arrow-up-short-wide'
            "
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import { DefaultSessionSortOrder } from '@/types/enum/SessionSortOrder';
import { SessionSortOrderOption } from '@/types/api/SessionOrderGroup';
import * as sessionService from '@/services/session-service';

export interface SessionFilterData {
  orderType: string;
  orderAsc: boolean;
  textFilter: string;
}

export const defaultFilterData: SessionFilterData = {
  orderType: DefaultSessionSortOrder,
  orderAsc: true,
  textFilter: '',
};
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class SessionFilter extends Vue {
  @Prop({
    default: { ...defaultFilterData },
  })
  modelValue!: SessionFilterData;
  sessionSortOrderOptions: SessionSortOrderOption[] = [];

  mounted(): void {
    this.sessionSortOrderOptions = sessionService.getSessionSortOrderOptions();
  }
  changeOrderAsc(): void {
    this.modelValue.orderAsc = !this.modelValue.orderAsc;
  }
}
</script>

<style lang="scss" scoped>
.el-input::v-deep(.el-input__prefix-inner) {
  padding: 0 0.5rem;
  margin: auto;
}

.link {
  background-color: var(--color-background-gray);
  z-index: 10;
  padding: 0 0.5rem;
}

.filter_options {
  margin: 5px;
  overflow-x: auto;
  scrollbar-color: var(--color-primary) var(--color-gray);
  scrollbar-width: thin;
  flex-direction: row-reverse;
}

.disabled {
  color: var(--color-purple);
}

.el-select,
.el-input {
  width: 12rem;
}
</style>
