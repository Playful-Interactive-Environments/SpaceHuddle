<template>
  <div class="level filter_options">
    <div class="level-left">
      <div
        class="level-item link"
        :class="{ disabled: !syncToPublicScreen }"
        @click="linkWithPublicScreen"
      >
        <font-awesome-icon icon="link" v-if="syncToPublicScreen" />
        <font-awesome-icon icon="link-slash" v-else />
      </div>
    </div>
    <div class="level-right">
      <div class="level-item">
        <el-input v-model="modelValue.textFilter" @change="change" clearable>
          <template #prefix>
            <font-awesome-icon icon="filter" />
          </template>
        </el-input>
      </div>
      <div class="level-item">
        <el-select
          v-model="modelValue.stateFilter"
          class="select--fullwidth"
          multiple
          @change="change"
        >
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

export interface FilterData {
  orderType: string;
  stateFilter: string[];
  textFilter: string;
}

export const defaultFilterData = {
  orderType: DefaultIdeaSortOrder,
  stateFilter: Object.keys(IdeaStates),
  textFilter: '',
};

@Options({
  emits: ['change', 'update'],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class IdeaFilter extends Vue {
  @Prop({
    default: { ...defaultFilterData },
  })
  modelValue!: FilterData;
  @Prop() readonly taskId!: string;
  task!: Task;
  syncUserId = '';
  ownUserId = '';

  sortOrderOptions: SortOrderOption[] = [];
  IdeaStates = IdeaStates;
  IdeaStateKeys = Object.keys(IdeaStates);

  readonly intervalTime = 10000;
  interval!: any;

  get syncToPublicScreen(): boolean {
    return this.syncUserId === this.ownUserId;
  }

  mounted(): void {
    this.ownUserId = authService.getUserId();
    this.startInterval();
  }

  startInterval(): void {
    this.interval = setInterval(this.getTask, this.intervalTime);
  }

  unmounted(): void {
    clearInterval(this.interval);
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.getTask();
  }

  async getTask(): Promise<void> {
    if (this.taskId && !this.isSaving) {
      await taskService.getTaskById(this.taskId).then(async (task) => {
        this.task = task;
        let sortOrderTaskId: string | null = null;
        if (TaskType[task.taskType] === TaskType.BRAINSTORMING) {
          sortOrderTaskId = task.id;
        } else if (
          task.parameter.input.length === 1 &&
          task.parameter.input[0].view.type.toLowerCase() === ViewType.TASK
        )
          sortOrderTaskId = task.parameter.input[0].view.id;
        ideaService.getSortOrderOptions(sortOrderTaskId).then((options) => {
          this.sortOrderOptions = options.filter(
            (option) => option.ref?.id !== this.taskId
          );
          if (task.parameter.input.length < 2) {
            this.sortOrderOptions = this.sortOrderOptions.filter(
              (option) => option.orderType !== IdeaSortOrder.INPUT.toLowerCase()
            );
          }
          if (options.length > 0 && !this.modelValue.orderType)
            this.modelValue.orderType = options[0].orderType;
        });

        if (task.parameter && 'syncUserId' in task.parameter) {
          this.syncUserId = task.parameter.syncUserId;
        } else {
          sessionRoleService.getOwn(task.sessionId).then((role) => {
            if (role.role === UserType.MODERATOR) {
              this.syncUserId = this.ownUserId;
            }
          });
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

          if (updateProperties) {
            this.$emit('update', this.modelValue);
            this.$emit('change', this.modelValue);
          }
        }
      });
    }
  }

  isSaving = false;
  saveParameterChanges(): void {
    if (this.taskId) {
      this.isSaving = true;
      taskService.getTaskById(this.taskId).then((task) => {
        if (this.syncToPublicScreen) {
          task.parameter.orderType = this.modelValue.orderType;
          task.parameter.stateFilter = this.modelValue.stateFilter;
          task.parameter.textFilter = this.modelValue.textFilter;
        } else if (!this.syncUserId) {
          task.parameter.orderType = defaultFilterData.orderType;
          task.parameter.stateFilter = defaultFilterData.stateFilter;
          task.parameter.textFilter = defaultFilterData.textFilter;
        }
        task.parameter.syncUserId = this.syncUserId;
        taskService.putTask(convertToSaveVersion(task)).then(() => {
          this.isSaving = false;
        });
      });
    }
  }

  linkWithPublicScreen(): void {
    if (this.syncToPublicScreen) this.syncUserId = '';
    else this.syncUserId = this.ownUserId;
    this.saveParameterChanges();
  }

  change(): void {
    this.$emit('update', this.modelValue);
    this.$emit('change', this.modelValue);
    if (this.syncToPublicScreen) this.saveParameterChanges();
  }
}
</script>

<style lang="scss" scoped>
.el-input::v-deep {
  .el-input__prefix-inner {
    padding: 0 0.5rem;
    margin: auto;
  }
}

.link {
  background-color: var(--color-background-gray);
  z-index: 10;
}

.filter_options {
  margin-bottom: 5px;
}

.disabled {
  color: var(--color-purple);
}
</style>
