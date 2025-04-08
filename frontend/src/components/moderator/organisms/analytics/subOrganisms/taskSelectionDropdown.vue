<template>
  <el-dropdown
    v-if="hasElements"
    @command="addToElementsArray"
    trigger="click"
    placement="bottom"
  >
    <div class="el-dropdown-link">
      <p class="oneLineText highscoreModuleName">
        <font-awesome-icon :icon="['fas', 'plus']" class="plus-icon" />
      </p>
    </div>
    <template #dropdown>
      <el-dropdown-menu>
        <template
          v-for="(el, index) in availableElements"
          :key="el.taskData.taskId"
        >
          <el-dropdown-item
            v-if="isTopicHeading(index)"
            class="heading oneLineText"
            :divided="true"
            :style="{ pointerEvents: 'none' }"
            disabled
          >
            {{ el.taskData.topicName }}
          </el-dropdown-item>
          <el-dropdown-item :command="el" :divided="isTopicHeading(index)">
            <font-awesome-icon
              class="axisIcon"
              :icon="getIconOfElement(el)"
              :style="{ color: getColorOfElement(el) }"
            />
            <span>&nbsp;{{ el.taskData.taskName }}</span>
          </el-dropdown-item>
        </template>
      </el-dropdown-menu>
    </template>
  </el-dropdown>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { getColorOfType, getIconOfType } from '@/types/enum/TaskCategory';
import TaskType from '@/types/enum/TaskType';
import { Prop, Watch } from 'vue-property-decorator';

interface TaskData {
  taskId: string;
  taskType: TaskType;
  taskName: string;
  topicName: string;
  topicOrder: number;
  moduleName: string;
  initOrder?: number;
}

interface TaskDataObject {
  taskData: TaskData;
  /* eslint-disable-next-line @typescript-eslint/no-explicit-any*/
  [key: string]: any;
}

@Options({
  emits: ['update:elements'],
})
export default class taskSelectionDropdown extends Vue {
  @Prop({
    type: Array,
    required: true,
    validator: function (value: TaskDataObject[]) {
      return Array.isArray(value);
    },
  })
  elements!: TaskDataObject[];
  @Prop({
    type: Array,
    required: true,
    validator: function (value: TaskDataObject[]) {
      return Array.isArray(value);
    },
  })
  availableElements!: TaskDataObject[];

  @Watch('elements', { deep: true })
  onValueChanged(newValue: { taskData: TaskData }[]) {
    this.$emit('update:elements', newValue);
  }

  get hasElements(): boolean {
    return this.availableElements.length >= 1;
  }

  getIconOfElement(el: TaskDataObject): string | undefined {
    return el.taskData.taskType
      ? getIconOfType(TaskType[el.taskData.taskType.toUpperCase()])
      : undefined;
  }

  getColorOfElement(el: TaskDataObject): string | undefined {
    return el.taskData.taskType
      ? getColorOfType(TaskType[el.taskData.taskType.toUpperCase()])
      : undefined;
  }

  addToElementsArray(el: TaskDataObject): void {
    this.elements.push(el);
  }

  isTopicHeading(index: number): boolean {
    return (
      (this.availableElements[index - 1] &&
        this.availableElements[index].taskData.topicOrder !==
          this.availableElements[index - 1].taskData.topicOrder) ||
      index === 0
    );
  }
}
</script>

<style scoped lang="scss">
.plus-icon {
  margin-top: 0.5rem;
  transform: scale(1);
  transition: transform 0.3s ease;
}

.plus-icon:hover {
  transform: scale(1.15);
}
</style>
