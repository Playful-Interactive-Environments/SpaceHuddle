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
        <el-select
          v-model="modelValue.subjects"
          :placeholder="
            $t('moderator.molecule.sessionFilter.subjectPlaceholder')
          "
          clearable
          multiple
          size="large"
        >
          <template #prefix>
            <font-awesome-icon icon="sort" class="el-icon" />
          </template>
          <el-option
            v-for="subject in subjectList"
            :key="subject"
            :value="subject"
            :label="subject"
          >
            <span>
              {{ subject }}
            </span>
          </el-option>
        </el-select>
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
          <ToolTip
            :text="
              modelValue.orderAsc
                ? $t('moderator.molecule.sessionFilter.sortAscending')
                : $t('moderator.molecule.sessionFilter.sortDescending')
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
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import { DefaultSessionSortOrder } from '@/types/enum/SessionSortOrder';
import { SessionSortOrderOption } from '@/types/api/SessionOrderGroup';
import * as sessionService from '@/services/session-service';
import * as cashService from '@/services/cash-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import ToolTip from '@/components/shared/atoms/ToolTip.vue';

export interface SessionFilterData {
  orderType: string;
  orderAsc: boolean;
  textFilter: string;
  subjects: string[] | null;
}

export const defaultFilterData: SessionFilterData = {
  orderType: DefaultSessionSortOrder,
  orderAsc: true,
  textFilter: '',
  subjects: null,
};

@Options({
  components: { ToolTip },
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class SessionFilter extends Vue {
  @Prop({
    default: { ...defaultFilterData },
  })
  modelValue!: SessionFilterData;
  sessionSortOrderOptions: SessionSortOrderOption[] = [];
  subjectList: string[] = [];
  subjectCash!: cashService.SimplifiedCashEntry<string[]>;

  mounted(): void {
    this.sessionSortOrderOptions = sessionService.getSessionSortOrderOptions();
    this.subjectCash = sessionService.registerGetSubjects(
      this.updateSubjects,
      EndpointAuthorisationType.MODERATOR,
      60 * 60
    );
  }
  changeOrderAsc(): void {
    this.modelValue.orderAsc = !this.modelValue.orderAsc;
  }
  updateSubjects(subjects: string[]): void {
    const tempList: string[] = [];
    subjects.forEach((subject) => {
      if (subject != null || subject != undefined) {
        tempList.push(subject);
      }
    });
    this.subjectList = tempList;
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateSubjects);
  }

  unmounted(): void {
    this.deregisterAll();
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

.filter_options {
  margin: 5px;
  overflow-x: auto;
  scrollbar-color: var(--color-primary) var(--color-gray);
  scrollbar-width: thin;
  flex-direction: row-reverse;
}

.disabled {
  color: var(--color-highlight);
}

.el-select,
.el-input {
  width: 12rem;
}
</style>
