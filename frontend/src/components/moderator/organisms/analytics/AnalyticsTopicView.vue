<template>
  <el-dialog
    v-model="showSettings"
    :before-close="handleClose"
    width="calc(var(--app-width) * 0.8)"
  >
    <template #header>
      <div :style="{ display: 'flex' }">
        <span class="el-dialog__title">{{
          $t('moderator.organism.settings.analytics.header')
        }}</span>
      </div>
    </template>
    <el-tree
      class="tree"
      ref="tree"
      style="max-width: 600px"
      :data="treeData"
      node-key="id"
      :default-checked-keys="selectedKeys"
      :props="{
        children: 'tasks',
        label: (data) => data.name || data.title,
        //disabled: (data) => data.participantCount === 0,
        disabled: false,
      }"
      show-checkbox
      @check-change="onCheckChange"
    >
      <template #default="{ data }">
        <span
          :style="{
            display: 'flex',
            justifyContent: 'space-between',
            width: '100%',
            paddingRight: '1rem',
          }"
        >
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
          <span v-if="data.participantCount >= 0">
            {{ data.participantCount }}
            <font-awesome-icon :icon="['fas', 'user']" />
          </span>
        </span>
      </template>
    </el-tree>
    <analytics :session-id="sessionId" :received-tasks="selectedTasks" />
  </el-dialog>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { defaultFormRules, ValidationRuleDefinition } from '@/utils/formRules';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Session } from '@/types/api/Session';
import * as cashService from '@/services/cash-service';
import { ParticipantInfo } from '@/types/api/Participant';
import QrcodeVue from 'qrcode.vue';
import * as themeColors from '@/utils/themeColors';
import PDFConverter from '@/components/shared/atoms/PDFConverter.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import * as topicService from '@/services/topic-service';
import { Topic } from '@/types/api/Topic';
import * as taskService from '@/services/task-service';
import { Task } from '@/types/api/Task';
import * as sessionService from '@/services/session-service';
import { getColorOfType, getIconOfType } from '@/types/enum/TaskCategory';
import Analytics from '@/components/moderator/organisms/analytics/analytics.vue';

@Options({
  methods: { getColorOfType, getIconOfType },
  components: {
    Analytics,
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
    const filterEmptyNodes = (node: any): boolean => {
      return !node.tasks || node.tasks.length > 0;
    };

    const processNode = (node: any): any => {
      if (node.tasks) {
        node.tasks = node.tasks.map(processNode).filter(filterEmptyNodes);
      }
      return node;
    };

    const returnData: any[] = [];
    for (const topic of this.topics) {
      const topicNode = {
        title: topic.title,
        tasks: [
          {
            title: this.$t(`enum.taskType.brainstorming`),
            taskType: 'BRAINSTORMING',
            tasks: topic.tasks?.filter(
              (task) => (task.taskType as string) === 'BRAINSTORMING'
            ),
          },
          {
            title: this.$t(`enum.taskType.information`),
            taskType: 'INFORMATION',
            tasks: topic.tasks?.filter(
              (task) => (task.taskType as string) === 'INFORMATION'
            ),
          },
          {
            title: this.$t(`enum.taskType.playing`),
            taskType: 'PLAYING',
            tasks: topic.tasks?.filter(
              (task) => (task.taskType as string) === 'PLAYING'
            ),
          },
          {
            title: this.$t(`enum.taskType.voting`),
            taskType: 'VOTING',
            tasks: topic.tasks?.filter(
              (task) => (task.taskType as string) === 'VOTING'
            ),
          },
        ],
      };

      // Process the topic node and its children
      returnData.push(processNode(topicNode));
    }
    return [
      {
        title: this.$t('moderator.organism.analytics.taskSelection'),
        tasks: returnData.filter(filterEmptyNodes),
      },
    ];
  }

  onCheckChange(): void {
    // Get all checked nodes
    const treeRef = this.$refs.tree as any; // Use a reference to the ElTree component
    const checkedNodes = treeRef.getCheckedNodes(true); // Get all checked nodes, including child nodes

    // Filter only tasks (leaf nodes)
    this.selectedTasks = checkedNodes
      .filter((node: any) => !node.tasks)
      .sort((a, b) => Number(`${a.topicOrder}000${a.order}`) - Number(`${b.topicOrder}000${b.order}`));
  }

  get selectedKeys(): string[] {
    return this.selectedTasks.map((task) => task.id);
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
.tree {
  position: absolute;
  z-index: 10000;
  right: calc(var(--el-dialog-padding-primary) * 1.5);
  top: var(--el-dialog-padding-primary);
  width: 25rem;
  border-radius: var(--border-radius-small);
  ::v-deep(.el-tree-node) {
    border-radius: var(--border-radius-small);
    padding: 0.2rem 0.4rem;
  }
  ::v-deep(.el-checkbox) {
    transform: scale(0.5);
  }
}
</style>
