<template>
  <draggable
    v-if="hasData"
    v-model="elementsData"
    v-bind="dragOptions"
    item-key="id"
    class="draggable-card-container"
    :group="{
      name: 'DraggableCards',
      pull: 'true',
      put: false,
    }"
  >
    <template v-slot:item="{ element, index }">
      <el-card
        :id="element.taskData.taskId + 'card' + index"
        class="draggable-card-selection-container"
        shadow="never"
        body-style="text-align: center"
        :class="{ addOn__boarder: !element }"
      >
        <div class="card-selection-header">
          <el-dropdown
            v-if="hasDropdownData"
            @command="(command) => updateElements(index, command)"
            trigger="click"
            placement="bottom"
          >
            <div class="el-dropdown-link">
              <slot name="card-header-icon" :element="element"></slot>
              <ToolTip>
                <template #content>
                  <div :style="{ textAlign: 'center' }">
                    <p class="heading heading--white">
                      {{
                        `${$t('moderator.molecule.moduleCount.topics')} ${
                          element.taskData.topicOrder + 1
                        }: ${element.taskData.topicName}`
                      }}
                    </p>
                    <p>{{ element.taskData.taskName }}</p>
                  </div>
                </template>
                <p class="oneLineText card-name">
                  T{{ element.taskData.topicOrder + 1 }}:&nbsp;{{
                    element.taskData.taskName
                  }}
                </p>
              </ToolTip>
              <font-awesome-icon :icon="['fas', 'angle-down']" />
              <span class="participant-count card-name"
                ><font-awesome-icon icon="user" />&nbsp;{{
                  getParticipantCount(element)
                }}
              </span>
            </div>
            <template #dropdown>
              <el-dropdown-menu>
                <template
                  v-for="(item, dropdownIndex) in dropdownItems"
                  :key="item.taskData.taskId"
                >
                  <el-dropdown-item
                    v-if="isTopicHeading(dropdownIndex)"
                    class="heading oneLineText"
                    :divided="true"
                    :style="{ pointerEvents: 'none' }"
                    disabled
                  >
                    {{ item.taskData.topicName }}
                  </el-dropdown-item>
                  <el-dropdown-item
                    :command="item"
                    :divided="isTopicHeading(dropdownIndex)"
                  >
                    <slot name="dropdown-item-icon" :item="item"></slot>
                    <span>&nbsp;{{ item.taskData.taskName }}</span>
                  </el-dropdown-item>
                </template>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
          <font-awesome-icon
            v-if="element"
            :icon="['fas', 'trash']"
            class="trashButton"
            @click="removeElement(index)"
          />
        </div>

        <slot
          name="item-content"
          :element="element"
          :selected-participant-ids="participantIds"
          :updateSelectedParticipantIds="updateSelectedParticipantIds"
          @update:selected-participant-ids="updateSelectedParticipantIds"
        ></slot>

        <ToolTip
          :text="
            this.expandedElements.find(
              (elId) => elId === element.taskData.taskId + 'card' + index
            )
              ? $t('moderator.organism.analytics.collapse')
              : $t('moderator.organism.analytics.expand')
          "
          :placement="'bottom'"
        >
          <div
            class="expandCard"
            @click="handleExpandClick(element.taskData.taskId + 'card' + index)"
          >
            <font-awesome-icon
              v-if="
                !this.expandedElements.find(
                  (elId) => elId === element.taskData.taskId + 'card' + index
                )
              "
              :icon="['fas', 'chevron-right']"
              class="expandIcon"
            />
            <font-awesome-icon
              v-else
              :icon="['fas', 'chevron-left']"
              class="expandIcon"
            />
          </div>
        </ToolTip>
      </el-card>
    </template>
  </draggable>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import ToolTip from '@/components/shared/atoms/ToolTip.vue';
import draggable from 'vuedraggable';
import TaskType from '@/types/enum/TaskType';
import { Avatar } from '@/types/api/Participant';

interface Answer {
  avatar: Avatar;
  answer: string[];
  correct?: boolean[] | null;
}

interface QuestionData {
  question: string;
  questionType: string;
  parameter: {
    minValue?: number;
    maxValue?: number;
  };
  answers: Answer[];
}

interface subAxis {
  id: string;
  range: number;
}
interface SurveyData {
  taskData: {
    moduleName: string;
    taskId: string;
    taskName: string;
    taskType: TaskType;
    topicName: string;
    topicOrder: number;
  };
  questions: QuestionData[];
}

interface RadarData {
  taskData: {
    moduleName: string;
    taskId: string;
    taskName: string;
    taskType: TaskType;
    topicName: string;
    topicOrder: number;
  };
  test: string;
  labels: string[];
  data: { data: number[]; avatar: Avatar }[];
}

interface Axis {
  taskData: {
    taskId: string;
    taskType: TaskType;
    taskName: string;
    topicName: string;
    topicOrder: number;
    moduleName: string;
    initOrder: number;
  };
  axisValues: (subAxis | null)[];
  categoryActive: string;
  active: boolean;
  available: boolean;
}

@Options({
  components: { draggable, ToolTip },
  emits: [
    'update:selectedParticipantIds',
    'update:selectedElements',
    'hasExpandedElement',
    'update:expanded',
  ],
})
export default class DraggableCardList extends Vue {
  @Prop({ required: true }) expanded!: string;
  @Prop({ required: true }) readonly chartData!:
    | RadarData[]
    | SurveyData[]
    | Axis[];
  @Prop({ required: true }) readonly selectedElements!:
    | RadarData[]
    | SurveyData[]
    | Axis[];
  @Prop({ default: () => [] }) selectedParticipantIds!: string[];
  @Prop({ default: () => [] }) dropdownItems!:
    | RadarData[]
    | SurveyData[]
    | Axis[];
  // eslint-disable-next-line @typescript-eslint/no-explicit-any, @typescript-eslint/no-unused-vars
  @Prop({ default: () => (item: any) => 0 }) getParticipantCount!: (
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    item: any
  ) => number;

  elementsData: RadarData[] | SurveyData[] | Axis[] = [];
  expandedElements: string[] = [];
  participantIds: string[] = [];

  get hasData(): boolean {
    return !!this.chartData && this.chartData.length > 0;
  }

  get hasDropdownData(): boolean {
    return this.dropdownItems.length >= 1;
  }

  get dragOptions(): object {
    return {
      animation: 200,
      group: 'description',
      ghostClass: 'ghost',
    };
  }

  hasExpandedElement(): boolean {
    return document.getElementsByClassName('expanded').length > 0;
  }

  @Watch('selectedElements', { deep: true, immediate: true })
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  onSelectedElementsChanged(newValue: any[]) {
    this.elementsData = [...newValue];
    this.$emit('update:selectedElements', newValue);
  }

  @Watch('selectedParticipantIds', { immediate: true })
  onSelectedParticipantIdsChanged(): void {
    this.participantIds = this.selectedParticipantIds;
  }

  updateSelectedParticipantIds(ids: string[]): void {
    this.participantIds = ids;
    this.$emit('update:selectedParticipantIds', this.participantIds);
  }

  updateHasExpandedElements(): void {
    this.$emit('update:expanded', this.hasExpandedElement());
  }

  removeElement(index: number): void {
    this.selectedElements.splice(index, 1);
  }

  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  updateElements(index: number, element: any): void {
    this.selectedElements.splice(index, 1, element);
  }

  isTopicHeading(index: number): boolean {
    return (
      (this.dropdownItems[index - 1] &&
        this.dropdownItems[index].taskData.topicOrder !==
          this.dropdownItems[index - 1].taskData.topicOrder) ||
      index === 0
    );
  }

  handleExpandClick(id: string): void {
    const element = document.getElementById(id);
    if (element) {
      if (element.classList.contains('expanded')) {
        element.classList.remove('expanded');
        this.expandedElements = this.expandedElements.filter(
          (elId) => elId !== id
        );
      } else {
        element.classList.add('expanded');
        this.expandedElements.push(id);
      }
      this.updateHasExpandedElements();
    }
  }
}
</script>

<style lang="scss" scoped>
.draggable-card-container {
  position: relative;
  display: flex;
  justify-content: flex-start;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 3rem;
  width: 100%;
}

.draggable-card-selection-container {
  cursor: move;
  min-width: 700px;
  width: calc(50% - 1.5rem);
  overflow: visible;
  position: relative;
  background-color: var(--color-background);

  transition: width 0.4s ease;

  @media (max-width: calc((700px * 2) + 12rem)) {
    width: 100%;
  }

  @media print {
    width: 100%;
  }

  &::-webkit-scrollbar {
    display: none;
  }
}

.expanded {
  width: 100% !important;
}

.card-selection-header {
  display: flex;
  justify-content: space-between;
  align-items: center;

  .trashButton {
    background-color: transparent;
    padding: 0;
    margin: 0;
    font-size: var(--font-size-small);
    cursor: pointer;
  }
}

.card-name {
  font-size: var(--font-size-default);
  font-weight: var(--font-weight-bold);
}

.el-dropdown-link {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
  border-radius: var(--border-radius-xs);
  border: 2px solid transparent;
  padding: 0.2rem 0.6rem;
  transition: border 0.3s ease;
  cursor: pointer;

  &:hover {
    border: 2px solid var(--color-background-darker);
  }
}

.participant-count {
  margin-left: 1rem;
}

.expandCard {
  position: absolute;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 1.2rem;
  height: 1.2rem;
  right: -1.2rem;
  top: 1rem;
  font-size: 0.7rem;
  transition: background-color 0.4s ease;
  color: var(--color-background);
  background-color: var(--color-background-darker);
  border-radius: 0 var(--border-radius-small) var(--border-radius-small) 0;
  cursor: pointer;
  .expandIcon {
    display: flex;
    align-items: center;
    justify-content: center;
  }
}
.expandCard:hover {
  background-color: var(--color-evaluating);
}

.ghost {
  opacity: 0.5;
  background: var(--color-background-dark);
}
</style>
