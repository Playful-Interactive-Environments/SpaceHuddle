<template>
  <el-dropdown
    v-if="hasElements"
    @command="(command) => handleElementArrayUpdate(index, command)"
    trigger="click"
    placement="bottom"
  >
    <div class="el-dropdown-link">
      <p class="oneLineText highscoreModuleName">
        <slot />
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
import ToolTip from '@/components/shared/atoms/ToolTip.vue';

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
  components: { ToolTip },
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
  @Prop({ default: 'add' }) mode!: string;

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

  handleElementArrayUpdate(index: number, el: TaskDataObject) {
    if (this.mode === 'add') {
      this.addToElementsArray(el);
    } else if (this.mode === 'update') {
      this.updateElementsArray(index, el);
    }
  }

  addToElementsArray(el: TaskDataObject): void {
    this.elements.push(el);
  }

  updateElementsArray(index: number, el: TaskDataObject): void {
    this.elements.splice(index, 1, el);
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
.highscoreModuleName {
  font-size: var(--font-size-large);
  transition: color 0.3s ease;
}

.highscoreModuleName:hover {
  color: var(--color-evaluating);
}
</style>
