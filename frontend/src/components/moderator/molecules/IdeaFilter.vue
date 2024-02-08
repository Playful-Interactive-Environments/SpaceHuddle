<template>
  <div class="level filter_options">
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
      <div
        class="level-item link"
        :class="{ disabled: !syncToPublicScreen }"
        @click="linkWithPublicScreen"
      >
        <ToolTip
          :text="
            syncToPublicScreen
              ? $t('moderator.molecule.ideaFilter.syncDisable')
              : $t('moderator.molecule.ideaFilter.syncActive')
          "
        >
          <font-awesome-icon icon="link" v-if="syncToPublicScreen" />
          <font-awesome-icon icon="link-slash" v-else />
        </ToolTip>
      </div>
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
import { Prop, Watch } from 'vue-property-decorator';
import * as taskService from '@/services/task-service';
import ViewType from '@/types/enum/ViewType';
import * as ideaService from '@/services/idea-service';
import { convertToSaveVersion, Task } from '@/types/api/Task';
import * as authService from '@/services/auth-service';
import * as sessionRoleService from '@/services/session-role-service';
import UserType from '@/types/enum/UserType';
import TaskType from '@/types/enum/TaskType';
import { CollapseIdeas } from '@/components/moderator/organisms/cards/IdeaCard.vue';
import { ElMessage } from 'element-plus';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { SessionRole } from '@/types/api/SessionRole';
import * as cashService from '@/services/cash-service';
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

export const getFilterForTask = (task: Task): FilterData => {
  const filter = { ...defaultFilterData };

  if (task.parameter && task.parameter.orderType)
    filter.orderType = task.parameter.orderType;
  if (task.parameter && task.parameter.stateFilter)
    filter.stateFilter = task.parameter.stateFilter;
  if (task.parameter && task.parameter.textFilter)
    filter.textFilter = task.parameter.textFilter;
  if (task.parameter && 'collapseIdeas' in task.parameter)
    filter.collapseIdeas = task.parameter.collapseIdeas;
  if (task.parameter && 'orderAsc' in task.parameter)
    filter.orderAsc = task.parameter.orderAsc;

  return filter;
};

@Options({
  components: { ToolTip },
  emits: ['change', 'update'],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class IdeaFilter extends Vue {
  @Prop({
    default: { ...defaultFilterData },
  })
  modelValue!: FilterData;
  @Prop() readonly taskId!: string;
  @Prop({ default: true }) readonly useStateFilter!: boolean;
  task!: Task;
  syncUserId = '';
  ownUserId = '';

  sortOrderOptions: SortOrderOption[] = [];
  IdeaStates = IdeaStates;
  IdeaStateKeys = Object.keys(IdeaStates);
  sessionId = '';
  collapse = true;

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.deregisterAll();
    taskService.registerGetTaskById(this.taskId, this.updateTask);
  }

  @Watch('modelValue.collapseIdeas', { immediate: true })
  onCollapseIdeasChanged(): void {
    if (this.task && this.syncToPublicScreen) this.saveParameterChanges();
  }

  @Watch('sessionId', { immediate: true })
  onSessionIdChanged(): void {
    if (this.sessionId) {
      cashService.deregisterAllGet(this.updateRole);
      sessionRoleService.registerGetOwn(
        this.sessionId,
        this.updateRole,
        EndpointAuthorisationType.MODERATOR,
        60 * 60
      );
    }
  }

  sessionRole: UserType = UserType.MODERATOR;
  updateRole(role: SessionRole): void {
    this.sessionRole = role.role;
  }

  get isCollapseActive(): boolean {
    return this.modelValue.collapseIdeas === CollapseIdeas.collapseAll;
  }

  get isExpandActive(): boolean {
    return this.modelValue.collapseIdeas === CollapseIdeas.expandAll;
  }

  get syncToPublicScreen(): boolean {
    return this.syncUserId === this.ownUserId;
  }

  mounted(): void {
    this.ownUserId = authService.getUserId();
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateTask);
    cashService.deregisterAllGet(this.updateDependentTasks);
    cashService.deregisterAllGet(this.updateRole);
  }

  unmounted(): void {
    this.deregisterAll();
  }

  getSortOrderTaskIds(): string[] {
    const result: string[] = [];
    if (TaskType[this.task.taskType] === TaskType.BRAINSTORMING) {
      result.push(this.task.id);
    } else {
      for (const input of this.task.parameter.input) {
        if (input.view.type.toLowerCase() === ViewType.TASK)
          result.push(input.view.id);
      }
    }
    return result;
  }

  updateTask(task: Task): void {
    if (!this.isSaving) {
      this.task = task;
      this.sessionId = task.sessionId;
      const sortOrderTaskIds = this.getSortOrderTaskIds();
      this.dependentTaskData = {};
      if (sortOrderTaskIds.length > 0) {
        for (const sortOrderTaskId of sortOrderTaskIds) {
          taskService.registerGetDependentTaskList(
            sortOrderTaskId,
            this.updateDependentTasks
          );
        }
      } else {
        this.updateDependentTasks([], this.taskId);
      }
      if (task.parameter && 'syncUserId' in task.parameter) {
        this.syncUserId = task.parameter.syncUserId;
      } else {
        if (this.sessionRole === UserType.MODERATOR) {
          this.syncUserId = this.ownUserId;
        }
      }
      if (this.syncToPublicScreen) {
        let updateProperties = false;
        if (
          task.parameter &&
          task.parameter.orderType &&
          this.modelValue.orderType !== task.parameter.orderType
        ) {
          this.modelValue.orderType = task.parameter.orderType;
          updateProperties = true;
        }
        if (
          task.parameter &&
          task.parameter.stateFilter &&
          this.modelValue.stateFilter.length !==
            task.parameter.stateFilter.length
        ) {
          this.modelValue.stateFilter = task.parameter.stateFilter;
          updateProperties = true;
        }
        if (
          task.parameter &&
          task.parameter.textFilter &&
          this.modelValue.textFilter !== task.parameter.textFilter
        ) {
          this.modelValue.textFilter = task.parameter.textFilter;
          updateProperties = true;
        }
        if (
          task.parameter &&
          task.parameter.collapseIdeas &&
          this.modelValue.collapseIdeas !== task.parameter.collapseIdeas
        ) {
          this.modelValue.collapseIdeas = task.parameter.collapseIdeas;
          updateProperties = true;
        }

        if (updateProperties) {
          this.$emit('update', this.modelValue);
          this.$emit('change', this.modelValue);
        }
      }
    }
  }

  dependentTaskData: { [key: string]: Task[] } = {};
  updateDependentTasks(data: Task[], taskId: string): void {
    this.dependentTaskData[taskId] = data;
    const allData: Task[] = [];
    for (const input of Object.values(this.dependentTaskData))
      allData.push(...input);
    const options = ideaService.getSortOrderOptions(allData);
    this.sortOrderOptions = options.filter(
      (option) => option.ref?.id !== this.taskId
    );
    if (this.task?.parameter?.input && this.task.parameter.input.length < 2) {
      this.sortOrderOptions = this.sortOrderOptions.filter(
        (option) => option.orderType !== IdeaSortOrder.INPUT.toLowerCase()
      );
    }
    if (options.length > 0 && !this.modelValue.orderType)
      this.modelValue.orderType = options[0].orderType;
  }

  isSaving = false;
  saveParameterChanges(): void {
    if (this.task) {
      this.isSaving = true;
      if (this.syncToPublicScreen) {
        this.task.parameter.orderType = this.modelValue.orderType;
        this.task.parameter.orderAsc = this.modelValue.orderAsc;
        this.task.parameter.stateFilter = this.modelValue.stateFilter;
        this.task.parameter.textFilter = this.modelValue.textFilter;
        this.task.parameter.collapseIdeas = this.modelValue.collapseIdeas;
      } else if (!this.syncUserId) {
        this.task.parameter.orderType = defaultFilterData.orderType;
        this.task.parameter.orderAsc = defaultFilterData.orderAsc;
        this.task.parameter.stateFilter = defaultFilterData.stateFilter;
        this.task.parameter.textFilter = defaultFilterData.textFilter;
        this.task.parameter.collapseIdeas = defaultFilterData.collapseIdeas;
      }
      this.task.parameter.syncUserId = this.syncUserId;
      taskService.putTask(convertToSaveVersion(this.task)).then(() => {
        this.isSaving = false;
      });
    }
  }

  linkWithPublicScreen(): void {
    if (this.syncToPublicScreen) this.syncUserId = '';
    else this.syncUserId = this.ownUserId;
    this.saveParameterChanges();
    ElMessage({
      message: (this as any).$t(
        `moderator.molecule.ideaFilter.${
          this.syncToPublicScreen
            ? 'syncActiveMessage'
            : 'syncDeactivateMessage'
        }`
      ),
      type: this.syncToPublicScreen ? 'success' : 'error',
      center: true,
      showClose: true,
    });
  }

  change(): void {
    this.$emit('update', this.modelValue);
    this.$emit('change', this.modelValue);
    if (this.syncToPublicScreen) this.saveParameterChanges();
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

.filter_options {
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
