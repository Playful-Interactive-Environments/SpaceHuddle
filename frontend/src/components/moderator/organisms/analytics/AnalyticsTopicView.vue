<template>
  <el-dialog
    v-model="showSettings"
    :before-close="handleClose"
    width="calc(var(--app-width) * 0.8)"
  >
    <template #header>
      <span class="el-dialog__title"> test </span>
    </template>
    <el-tree
      ref="tree"
      style="max-width: 600px"
      :data="treeData"
      :props="{
        children: 'tasks',
        label: (treeDataEntry) => treeDataEntry.name || treeDataEntry.title,
        disabled: false,
      }"
      show-checkbox
      @check-change="onCheckChange"
    >
      <template #default="{ data }">
        <span>
          <font-awesome-icon
            v-if="data.taskType"
            :icon="getIconOfType(data.taskType.toLowerCase())"
            :style="{
              color: getColorOfType(data.taskType.toLowerCase()),
            }"
          />
          {{ data.name || data.title }}
        </span>
      </template>
    </el-tree>
  </el-dialog>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { defaultFormRules, ValidationRuleDefinition } from '@/utils/formRules';
import * as sessionService from '@/services/session-service';
import * as participantService from '@/services/participant-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Session } from '@/types/api/Session';
import * as cashService from '@/services/cash-service';
import { ParticipantInfo } from '@/types/api/Participant';
import QrcodeVue from 'qrcode.vue';
import * as themeColors from '@/utils/themeColors';
import { copyToClipboard } from '@/utils/date';
import PDFConverter from '@/components/shared/atoms/PDFConverter.vue';
import { deleteConfirmDialog } from '@/services/api';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import * as topicService from '@/services/topic-service';
import { registerGetTopicsList } from '@/services/topic-service';
import { Topic } from '@/types/api/Topic';
import * as taskService from '@/services/task-service';
import { Task } from '@/types/api/Task';
import { getColorOfType, getIconOfType } from '@/types/enum/TaskCategory';
import TaskType from '@/types/enum/TaskType';

@Options({
  methods: { getColorOfType, getIconOfType },
  components: {
    FontAwesomeIcon,
    QrcodeVue,
    PDFConverter,
  },
  emits: ['update:showModal'],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class AnalyticsTopicView extends Vue {
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;

  @Prop({ default: false }) showModal!: boolean;
  @Prop({ default: '' }) sessionId!: string;

  topics: Topic[] = [];
  session: Session | null = null;
  participants: ParticipantInfo[] = [];
  viewDetailsForParticipant: ParticipantInfo | null = null;

  showSettings = false;

  selectedTasks: Task[] = [];
  topicsCashEntry!: cashService.SimplifiedCashEntry<Topic[]>;

  updateTopics(topics: Topic[]): void {
    this.topics = topics;
    this.topics.forEach(async (topic) => {
      taskService.registerGetTaskList(
        topic.id,
        this.updateTasks,
        EndpointAuthorisationType.MODERATOR,
        5 * 60
      );
    });
  }

  @Watch('sessionId', { immediate: true })
  async onSessionIdChanged(): Promise<void> {
    this.topicsCashEntry = topicService.registerGetTopicsList(
      this.sessionId,
      this.updateTopics,
      EndpointAuthorisationType.MODERATOR,
      2 * 60
    );
  }

  updateTasks(tasks: Task[], topicId: string): void {
    const topic = this.topics.find((topic) => topic.id === topicId);
    if (topic) {
      topic.tasks = tasks;
      topic.tasks.sort((a, b) => (a.order > b.order ? 1 : 0));
    }
  }

  get treeData(): any {
    const returnData: any[] = [];
    for (const topic of this.topics) {
      returnData.push({
        title: topic.title,
        tasks: [
          {
            title: 'brainstorming',
            tasks: topic.tasks?.filter(
              (task) => (task.taskType as string) === 'BRAINSTORMING'
            ),
          },
          {
            title: 'information',
            tasks: topic.tasks?.filter(
              (task) => (task.taskType as string) === 'INFORMATION'
            ),
          },
          {
            title: 'playing',
            tasks: topic.tasks?.filter(
              (task) => (task.taskType as string) === 'PLAYING'
            ),
          },
          {
            title: 'voting',
            tasks: topic.tasks?.filter(
              (task) => (task.taskType as string) === 'VOTING'
            ),
          },
        ],
      });
    }
    return returnData;
  }

  onCheckChange(): void {
    // Get all checked nodes
    const treeRef = this.$refs.tree as any; // Use a reference to the ElTree component
    const checkedNodes = treeRef.getCheckedNodes(true); // Get all checked nodes, including child nodes

    // Filter only tasks (leaf nodes)
    this.selectedTasks = checkedNodes.filter((node: any) => !node.tasks);

    console.log('Selected Tasks:', this.selectedTasks);
  }

  get contrastColor(): string {
    return themeColors.getContrastColor();
  }

  get backgroundColor(): string {
    return themeColors.getBackgroundColor();
  }

  handleClose(done: { (): void }): void {
    this.viewDetailsForParticipant = null;
    done();
    this.$emit('update:showModal', false);
  }

  @Watch('showModal', { immediate: true })
  async onShowModalChanged(showModal: boolean): Promise<void> {
    this.showSettings = showModal;
  }
}
</script>

<style lang="scss" scoped>
.el-form-item .el-form-item {
  margin-bottom: 1rem;
}

.el-button.is-circle {
  padding: 0.7rem;
}

.awesome-icon {
  margin-left: 0.5em;
}

.el-table::v-deep(.cell) {
  span {
    margin-right: 0.5rem;
  }
}

.allow.el-form-item::v-deep(.el-form-item__content) {
  .el-form-item__content {
    display: flex;
    justify-content: space-between;
  }
}

.details {
  margin: auto;
  display: flex;
  align-items: center;
  flex-direction: row;
  justify-content: space-evenly;

  h1 {
    font-size: 10rem;
    font-weight: var(--font-weight-semibold);
    line-height: 1.8;
  }

  .details-right {
    margin-left: var(--side-padding);
    font-size: 1.35rem;
    font-family: monospace;
    svg {
      display: flex;
    }
  }

  .details-left {
    font-size: 1.35rem;
    font-family: monospace;
    margin: 2rem 0;
    svg {
      display: flex;
    }
  }
}

.pdf {
  margin: 100px;

  h2 {
    font-size: 3rem;
    font-weight: bold;
    text-align: center;
    padding-top: 1rem;
  }

  .center {
    text-align: center;
    padding-bottom: 1rem;
  }

  p {
    font-size: 1rem;
  }
}

.fullwidth {
  display: flex;
  .el-button {
    flex-grow: 1;
    margin-left: 0.5rem;
  }
}
</style>
