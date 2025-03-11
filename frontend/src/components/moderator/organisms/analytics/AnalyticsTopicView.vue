<template>
  <el-dialog
    ref="analyticsContainer"
    v-model="showSettings"
    :before-close="handleClose"
    width="calc(var(--app-width) * 0.8)"
  >
    <template #header>
      <div :style="{ display: 'flex' }">
        <span class="el-dialog__title"
          >{{ $t('moderator.organism.settings.analytics.header') }}
          <el-button @click="exportToPDF" class="exportButton"
            ><font-awesome-icon :icon="['fas', 'file-export']"
          /></el-button>
        </span>
      </div>
    </template>
    <el-tree
      class="tree"
      ref="tree"
      :key="treeDataKey"
      style="max-width: 600px"
      :data="treeData"
      node-key="id"
      :default-checked-keys="selectedKeys"
      :props="{
        children: 'tasks',
        label: (treeData) => treeData.name || treeData.title,
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
    <div class="analyticsContainer">
      <analytics
        ref="analyticsComponent"
        :session-id="sessionId"
        :received-tasks="selectedTasks"
        :topics="topics"
      />
      <transition name="transition-anim">
        <section class="pdf-preview" v-if="pdfFile">
          <button @click.self="closePreview()">&times;</button>

          <iframe :src="pdfFile" width="100%" height="100%" />
        </section>
      </transition>
    </div>
  </el-dialog>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { defaultFormRules, ValidationRuleDefinition } from '@/utils/formRules';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
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
import { getColorOfType, getIconOfType } from '@/types/enum/TaskCategory';
import Analytics from '@/components/moderator/organisms/analytics/analytics.vue';
import { createPdf } from '@/utils/html2pdf';

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
      10
    );
  }

  updateTasks(tasks: Task[], topicId: string): void {
    const topic = this.topics.find((topic) => topic.id === topicId);

    if (topic) {
      topic.tasks = tasks;
      topic.tasks.sort((a, b) => (a.order > b.order ? 1 : 0));
    }
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateTopics);
    cashService.deregisterAllGet(this.updateTasks);
  }

  unmounted(): void {
    this.deregisterAll();
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

  get treeDataKey(): string {
    // Generate a unique key based on the structure of treeData
    return JSON.stringify(this.treeData);
  }

  onCheckChange(): void {
    // Get all checked nodes
    const treeRef = this.$refs.tree as any; // Use a reference to the ElTree component
    const checkedNodes = treeRef.getCheckedNodes(true); // Get all checked nodes, including child nodes

    // Filter only tasks (leaf nodes)
    this.selectedTasks = checkedNodes
      .filter((node: any) => !node.tasks)
      .sort(
        (a, b) =>
          Number(`${a.topicOrder}000${a.order}`) -
          Number(`${b.topicOrder}000${b.order}`)
      );
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

  pdfFile = null;
  isConverting = false;
  async exportToPDF(): Promise<void> {
    const analyticsComponent = this.$refs.analyticsComponent as Analytics;
    const element = analyticsComponent.$el;

    // Get the dimensions of the parent container
    console.log(analyticsComponent.$parent);
    if (analyticsComponent.$parent) {
      this.isConverting = true;
      const widthInPixels = analyticsComponent.$parent.$el.clientWidth;
      const heightInPixels = analyticsComponent.$parent.$el.clientHeight;

      const widthInInches = widthInPixels / 96;
      const heightInInches = heightInPixels / 96;

      const margin = 0.5;

      const pdf = createPdf(element, {
        margin: margin,
        filename: `Analytics.pdf`,
        image: {
          type: 'jpeg',
          quality: 0.98,
        },
        enableLinks: false,
        html2canvas: {
          scale: 2,
          useCORS: true,
        },
        jsPDF: {
          unit: 'in',
          format: [widthInInches + margin, heightInInches + margin],
          orientation: 'landscape',
        },
      }).then(() => {
        this.isConverting = false;
      });
      this.pdfFile = await pdf.output('bloburl');
    } else {
      console.error('Parent container not found');
    }
  }

  closePreview() {
    this.pdfFile = null;
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

.exportButton {
  margin-left: 0.5rem;
  padding: 0;
  background-color: transparent;
}

.exportButton:hover {
  background-color: transparent;
}

.analyticsContainer {
  width: 100%;
  height: 100%;
  background-color: var(--color-background);
}

.pdf-preview {
  position: fixed;
  width: 65%;
  min-width: 600px;
  height: 80vh;
  top: 100px;
  z-index: 9999999;
  left: 50%;
  transform: translateX(-50%);
  border-radius: 5px;
  box-shadow: 0 0 10px #00000048;

  button {
    position: absolute;
    top: -20px;
    left: -15px;
    width: 35px;
    height: 35px;
    background: #555;
    border: 0;
    box-shadow: 0 0 10px #00000048;
    border-radius: 50%;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    cursor: pointer;
  }

  iframe {
    border: 0;
  }
}

.transition-anim-enter-active,
.transition-anim-leave-active {
  transition: opacity 0.3s ease-in;
}

.transition-anim-enter,
.transition-anim-leave-to {
  opacity: 0;
}
</style>
