<template>
  <div v-if="showDialog" v-loading.fullscreen.lock="loading">
    <ValidationForm
      :form-data="formData"
      :use-default-submit="false"
      v-on:submitDataValid="save"
      v-on:reset="reset"
      :key="componentLoadIndex"
    >
      <el-dialog
        v-model="showDialog"
        :before-close="handleClose"
        :key="componentLoadIndex"
        width="calc(var(--app-width) * 0.8)"
      >
        <template #header>
          <div v-if="isEditStep">
            <span class="el-dialog__title">
              {{ $t('moderator.organism.settings.taskSettings.header') }}
              <el-dropdown>
                <span class="el-dropdown-link">
                  <font-awesome-icon icon="info-circle" />
                </span>
                <template #dropdown>
                  <el-dropdown-menu>
                    <el-dropdown-item v-on:click="reactivateTutorial">
                      {{ $t('tutorial.reactivate') }}
                    </el-dropdown-item>
                  </el-dropdown-menu>
                </template>
              </el-dropdown>
            </span>
            <br />
            <br />
            <p>
              {{ $t('moderator.organism.settings.taskSettings.info') }}
            </p>
          </div>
          <div v-else>
            <span class="el-dialog__title">
              {{
                $t('moderator.organism.settings.taskSettings.preview-header')
              }}
            </span>
            <br />
            <br />
            <p>
              {{ $t('moderator.organism.settings.taskSettings.preview-info') }}
            </p>
          </div>
        </template>
        <div v-if="isEditStep">
          <el-form-item
            prop="moduleListMain"
            :rules="[
              defaultFormRules.ruleSelection,
              { validator: validateModuleSelection },
            ]"
            v-if="possibleModuleTaskList.length > 1"
          >
            <template #label>
              <TutorialStep
                type="taskSettings"
                step="module"
                :order="7"
                :displayAllDuplicates="true"
              >
                <span>
                  {{
                    $t('moderator.organism.settings.taskSettings.moduleType')
                  }}
                </span>
              </TutorialStep>
            </template>
            <div class="level module-selection" v-if="useModuleFilter">
              <div class="level-left">
                <div class="level-item">
                  <el-tag v-for="tag in moduleSelectionMain" :key="tag">
                    {{
                      $t(
                        `module.${TaskType[tag.taskType.toUpperCase()]}.${
                          tag.moduleName
                        }.description.title`
                      )
                    }}
                  </el-tag>
                  <el-tag
                    v-for="tag in moduleSelectionAddOn"
                    :key="tag.moduleName"
                    :closable="true"
                    v-on:close="removeModuleType(tag)"
                  >
                    {{
                      $t(
                        `module.${TaskType[tag.taskType.toUpperCase()]}.${
                          tag.moduleName
                        }.description.title`
                      )
                    }}
                  </el-tag>
                </div>
              </div>
              <div class="level-right">
                <TutorialStep
                  type="taskSettings"
                  step="searchModule"
                  :order="8"
                  :displayAllDuplicates="true"
                >
                  <el-input
                    class="search"
                    v-model="moduleSearch"
                    :clearable="true"
                    :placeholder="
                      $t(
                        'moderator.organism.settings.taskSettings.moduleSearch'
                      )
                    "
                  >
                    <template #suffix>
                      <font-awesome-icon icon="search" class="el-icon" />
                    </template>
                  </el-input>
                </TutorialStep>
              </div>
            </div>
            <el-scrollbar ref="moduleScrollbar" always>
              <div class="flex-content">
                <ModuleCard
                  v-for="moduleType in displayModuleList"
                  :key="moduleType.moduleName"
                  :moduleTask="moduleType"
                  v-model:mainModule="mainModule"
                  v-model="moduleType.active"
                  :displayTutorial="displayModuleTutorial(moduleType)"
                />
                <TutorialStep
                  v-if="hideNotUsesModules"
                  type="taskSettings"
                  step="unusedModule"
                  :order="9"
                  :displayAllDuplicates="true"
                >
                  <AddItem
                    :text="
                      $t(
                        'moderator.organism.settings.taskSettings.displayAllModules'
                      )
                    "
                    :isColumn="true"
                    @addNew="hideNotUsesModules = false"
                    class="showMore"
                  />
                </TutorialStep>
              </div>
            </el-scrollbar>
          </el-form-item>
          <el-form-item v-if="showInput">
            <template #label>
              <TutorialStep
                type="taskSettings"
                step="input"
                :order="0"
                :displayAllDuplicates="true"
              >
                <span>
                  {{ $t('moderator.organism.settings.taskSettings.input') }}
                </span>
              </TutorialStep>
            </template>
            <table class="input-table">
              <colgroup>
                <col v-if="topics && topics.length > 1" style="width: 8rem" />
                <col />
                <col style="width: 12rem" />
                <col style="width: 12rem" />
                <col style="width: 10rem" />
                <col />
              </colgroup>
              <thead>
                <tr>
                  <th v-if="topics && topics.length > 1">
                    {{
                      $t('moderator.organism.settings.taskSettings.sourceTopic')
                    }}
                  </th>
                  <TutorialStep
                    step="inputSource"
                    type="taskSettings"
                    :order="1"
                    :displayAllDuplicates="true"
                  >
                    <th>
                      {{
                        $t('moderator.organism.settings.taskSettings.source')
                      }}
                    </th>
                  </TutorialStep>
                  <TutorialStep
                    step="inputFilter"
                    type="taskSettings"
                    :order="3"
                    :displayAllDuplicates="true"
                  >
                    <th>
                      {{
                        $t('moderator.organism.settings.taskSettings.filter')
                      }}
                    </th>
                  </TutorialStep>
                  <TutorialStep
                    step="inputOrder"
                    type="taskSettings"
                    :order="4"
                    :displayAllDuplicates="true"
                  >
                    <th>
                      {{ $t('moderator.organism.settings.taskSettings.order') }}
                    </th>
                  </TutorialStep>
                  <TutorialStep
                    step="inputMaxCount"
                    type="taskSettings"
                    :order="2"
                    :displayAllDuplicates="true"
                  >
                    <th>
                      {{
                        $t('moderator.organism.settings.taskSettings.maxCount')
                      }}
                    </th>
                  </TutorialStep>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="input in formData.input" :key="input.view">
                  <td v-if="topics && topics.length > 1">
                    <el-select
                      v-model="input.viewTopic"
                      class="select--fullwidth"
                    >
                      <el-option
                        v-for="topic in topics"
                        :key="topic.id"
                        :value="topic.id"
                        :label="topic.title"
                      >
                      </el-option>
                    </el-select>
                  </td>
                  <td>
                    <el-select
                      v-model="input.viewInput"
                      class="select--fullwidth"
                    >
                      <el-option
                        v-for="view in getPossibleViews(input.viewTopic)"
                        :key="view"
                        :value="getViewKey(view)"
                        :label="getViewName(view)"
                      >
                      </el-option>
                    </el-select>
                  </td>
                  <td>
                    <el-select
                      v-model="input.filter"
                      class="select--fullwidth"
                      multiple
                    >
                      <el-option
                        v-for="state in IdeaStateKeys"
                        :key="state"
                        :value="state"
                        :label="$t(`enum.ideaState.${IdeaStates[state]}`)"
                      >
                      </el-option>
                    </el-select>
                  </td>
                  <td>
                    <el-select
                      v-model="input.order"
                      class="select--fullwidth"
                      :disabled="input.isVoteInput"
                    >
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
                  </td>
                  <td style="display: inline-flex; align-items: center">
                    <el-switch
                      v-model="input.hasMaxCount"
                      style="padding-right: 0.5rem"
                    />
                    <el-input-number
                      :disabled="!input.hasMaxCount"
                      v-model="input.maxCountInput"
                      :min="1"
                    />
                  </td>
                  <td
                    v-on:click="deleteInput(input)"
                    v-if="formData.input.length > inputMinCount"
                  >
                    <font-awesome-icon class="link" icon="trash" />
                  </td>
                </tr>
              </tbody>
            </table>
            <TutorialStep
              v-if="
                possibleSessionViews.length > minAddModuleLength &&
                formData.input.length < maxInputs
              "
              step="inputAdd"
              type="taskSettings"
              :order="5"
              :displayAllDuplicates="true"
            >
              <AddItem
                :text="$t('moderator.organism.settings.taskSettings.addInput')"
                @addNew="addInput"
              />
            </TutorialStep>
          </el-form-item>
          <el-form-item
            prop="name"
            :rules="[
              defaultFormRules.ruleRequired,
              defaultFormRules.ruleToLong(255),
            ]"
          >
            <template #label>
              <TutorialStep
                type="taskSettings"
                step="title"
                :order="6"
                :displayAllDuplicates="true"
              >
                <span>
                  {{
                    taskTypes.includes(TaskType.BRAINSTORMING)
                      ? $t('moderator.organism.settings.taskSettings.question')
                      : $t('moderator.organism.settings.taskSettings.title')
                  }}
                </span>
              </TutorialStep>
            </template>
            <el-input
              v-model="formData.name"
              name="name"
              :placeholder="
                $t('moderator.organism.settings.taskSettings.questionExample')
              "
            />
          </el-form-item>
          <el-form-item
            :label="$t('moderator.organism.settings.taskSettings.description')"
            prop="description"
            :rules="[defaultFormRules.ruleToLong(1000)]"
          >
            <MarkdownEditor
              v-model="formData.description"
              :placeholder="
                $t(
                  'moderator.organism.settings.taskSettings.descriptionExample'
                )
              "
            />
          </el-form-item>
          <el-form-item
            :label="$t('moderator.organism.settings.taskSettings.keywords')"
            prop="keywords"
            :rules="[defaultFormRules.ruleToLong(50)]"
          >
            <el-input
              v-model="formData.keywords"
              :placeholder="
                $t('moderator.organism.settings.taskSettings.keywordsExample')
              "
            />
          </el-form-item>
          <TaskParameterComponent
            ref="taskParameter"
            :taskId="internalTaskId"
            :topicId="topicId"
            v-model="formData"
          />
          <el-collapse
            v-model="openTabs"
            :key="openTabs.length"
            v-if="
              formData.moduleParameterComponents.filter(
                (component) => component.hasModule
              ).length > 0
            "
          >
            <el-collapse-item
              v-for="component in formData.moduleParameterComponents
                .map((v, i) => ({ value: v, index: i }))
                .filter((item) => item.value.hasModule)"
              :key="component.value.componentName"
              :name="component.value.componentName"
            >
              <template #title>
                <span>
                  <font-awesome-icon
                    :icon="component.value.moduleIcon"
                    v-if="component.value.moduleIcon"
                  />
                  {{
                    $t(
                      `module.${TaskType[component.value.taskType]}.${
                        component.value.moduleName
                      }.description.title`
                    )
                  }}
                </span>
              </template>
              <component
                :ref="component.value.componentName"
                v-model="component.value.parameter"
                :module-id="component.value.moduleId"
                :formData="formData"
                :task-id="taskId"
                :topic-id="topicId"
                :views="possibleSessionViews"
                :is="component.value.componentName"
                :key="component.value.componentName"
                :rulePropPath="`moduleParameterComponents[${component.index}].parameter`"
                :taskType="taskType"
                @update="updateSyncPublicParticipant"
              ></component>
            </el-collapse-item>
          </el-collapse>
        </div>
        <section
          v-else
          class="layout__columns"
          v-loading.fullscreen.lock="previewLoading"
        >
          <IdeaCard
            v-for="(idea, index) in previewData"
            :idea="idea"
            :key="index"
            :is-editable="false"
          />
        </section>
        <template #footer>
          <div class="level">
            <FromSubmitItem
              class="level-item"
              v-if="isEditStep"
              :form-state-message="formData.stateMessage"
              submit-label-key="moderator.organism.settings.taskSettings.submit"
              :disabled="isSaving"
            />
            <el-button
              class="level-item"
              type="primary"
              v-on:click="saveAndActivate"
              v-if="isEditStep && !showPreview && canActivateModule"
            >
              {{
                $t('moderator.organism.settings.taskSettings.saveAndActivate')
              }}
            </el-button>
            <el-button
              class="level-item"
              v-on:click="isEditStep = true"
              v-if="!isEditStep"
            >
              <template #icon>
                <font-awesome-icon icon="pen" />
              </template>
              {{ $t('moderator.organism.settings.taskSettings.edit') }}
            </el-button>
            <el-button
              type="primary"
              v-on:click="confirm"
              v-if="!isEditStep"
              :disabled="isSaving"
              class="el-button--submit level-item"
            >
              {{ $t('moderator.organism.settings.taskSettings.confirm') }}
            </el-button>
            <el-button
              class="level-item"
              type="primary"
              v-on:click="confirmAndActivate"
              v-if="!isEditStep && canActivateModule"
              :disabled="isSaving"
            >
              {{
                $t(
                  'moderator.organism.settings.taskSettings.confirmAndActivate'
                )
              }}
            </el-button>
          </div>
        </template>
      </el-dialog>
    </ValidationForm>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as taskService from '@/services/task-service';
import * as moduleService from '@/services/module-service';
import * as viewService from '@/services/view-service';
import TaskType from '@/types/enum/TaskType';
import { Task } from '@/types/api/Task';
import TaskStates from '@/types/enum/TaskStates';
import { getSingleTranslatedErrorMessage } from '@/services/exception-service';
import {
  getAsyncModule,
  getAsyncTaskParameter,
  getEmptyComponent,
  getModuleConfig,
  getModulesForTaskType,
  hasModule,
  ModuleTask,
} from '@/modules';
import {
  CustomInit,
  CustomParameter,
  CustomSync,
} from '@/types/ui/CustomParameter';
import { EventType } from '@/types/enum/EventType';
import ModuleComponentType from '@/modules/ModuleComponentType';
import { Module } from '@/types/api/Module';
import ModuleCard from '@/components/moderator/organisms/cards/ModuleCard.vue';
import { ValidationRules } from '@/types/ui/ValidationRule';
import { defaultFormRules, ValidationRuleDefinition } from '@/utils/formRules';
import ValidationForm, {
  ValidationFormCall,
} from '@/components/shared/molecules/ValidationForm.vue';
import FromSubmitItem from '@/components/shared/molecules/FromSubmitItem.vue';
import { ModuleType } from '@/types/enum/ModuleType';
import TimerSettings from '@/components/moderator/organisms/settings/TimerSettings.vue';
import { getViewKey, getViewName, View } from '@/types/api/View';
import IdeaStates from '@/types/enum/IdeaStates';
import { SortOrderOption } from '@/types/api/OrderGroup';
import * as ideaService from '@/services/idea-service';
import AddItem from '@/components/moderator/atoms/AddItem.vue';
import ViewType from '@/types/enum/ViewType';
import TutorialStep from '@/components/shared/atoms/TutorialStep.vue';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Idea } from '@/types/api/Idea';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import * as cashService from '@/services/cash-service';
import { reactivateTutorial } from '@/services/tutorial-service';
import IdeaSortOrder from '@/types/enum/IdeaSortOrder';
import { until } from '@/utils/wait';
import * as topicService from '@/services/topic-service';
import { Topic } from '@/types/api/Topic';
import MarkdownEditor from '@/components/shared/molecules/MarkdownEditor.vue';

/* eslint-disable @typescript-eslint/no-explicit-any*/

interface ModuleComponentDefinition {
  componentName: string;
  taskType: string | null;
  moduleName: string | null;
  moduleIcon: string[] | null;
  moduleId: string | null;
  hasModule: boolean;
  parameter: any;
}

export class InputData {
  viewTopic: string | null = null;
  view = '';
  maxCount: number | null = null;
  filter: string[] = [];
  order = '';

  oldMaxCount = 10;

  constructor(
    view: string,
    maxCount: number | null,
    filter: string[],
    order: string,
    viewTopic: string | null = null
  ) {
    this.view = view;
    this.maxCount = maxCount;
    this.filter = filter;
    this.order = order;
    this.viewTopic = viewTopic;
  }

  get hasMaxCount(): boolean {
    return !!this.maxCount;
  }

  set hasMaxCount(value: boolean) {
    if (value) this.maxCount = this.oldMaxCount;
    else {
      if (this.maxCount) this.oldMaxCount = this.maxCount;
      this.maxCount = null;
    }
  }

  get maxCountInput(): number | undefined {
    return this.maxCount ? this.maxCount : undefined;
  }

  set maxCountInput(value: number | undefined) {
    this.maxCount = this.maxCount && value ? value : null;
  }

  get viewInput(): string {
    return this.view;
  }

  set viewInput(value: string) {
    this.view = value;
    if (this.isVoteInput) {
      this.order = IdeaSortOrder.ORDER;
    }
  }

  get isVoteInput(): boolean {
    return this.view.toLowerCase().startsWith(ViewType.VOTE);
  }
}

class ModuleTaskProperty extends ModuleTask {
  active = false;

  static createFromModuleTask(moduleTask: ModuleTask): ModuleTaskProperty {
    return new ModuleTaskProperty(moduleTask.taskType, moduleTask.moduleName);
  }

  static createEmpty(): ModuleTaskProperty {
    return ModuleTaskProperty.createFromModuleTask(ModuleTask.createEmpty());
  }
}

interface FormDataDefinition {
  name: string;
  description: string;
  keywords: string;
  parameter: any;
  input: InputData[];
  moduleListMain: ModuleTaskProperty[];
  moduleListAddOn: ModuleTaskProperty[];
  moduleParameterComponents: ModuleComponentDefinition[];
  stateMessage: string;
  call: string;
}

interface InputFilter {
  types: string[];
  detailTypes: string[];
  modules: string[];
}

enum InputOption {
  YES = 'yes',
  NO = 'no',
  OPTIONAL = 'optional',
}

@Options({
  components: {
    IdeaCard,
    TutorialStep,
    TimerSettings,
    ValidationForm,
    FromSubmitItem,
    ModuleCard,
    AddItem,
    MarkdownEditor,
    TaskParameterComponent: getEmptyComponent(),
  },
  emits: ['taskUpdated', 'showTimerSettings', 'update:showModal'],
})
export default class TaskSettings extends Vue {
  //#region Props
  @Prop({ default: false }) showModal!: boolean;
  @Prop({}) topicId!: string;
  @Prop({}) taskId!: string;
  @Prop({ default: [] }) taskTypes!: (keyof typeof TaskType)[];
  //#endregion Props

  //#region Variables
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;

  sessionId = '';
  topics: { id: string; title: string }[] = [];
  sessionViews: View[] = [];
  inputTopicId = '';
  componentLoadIndex = 0;
  usedModuleList: ModuleTaskProperty[] = [];
  userModuleList: ModuleTask[] = [];
  addOneListUnfiltered: ModuleTaskProperty[] = [];
  hideNotUsesModules = true;
  moduleSearch = '';
  syncPublicParticipant = false;

  formData: FormDataDefinition = {
    input: [],
    name: '',
    description: '',
    keywords: '',
    parameter: {},
    moduleListMain: [],
    moduleListAddOn: [],
    moduleParameterComponents: [],
    stateMessage: '',
    call: '',
  };
  openTabs: string[] = [];
  task: Task | null = null;
  inputOption: InputOption = InputOption.NO;
  inputFilter: InputFilter = {
    types: [],
    detailTypes: [],
    modules: [],
  };
  maxInputs = 20;
  sortOrderOptions: SortOrderOption[] = [];
  moduleScrollbar: { sizeWidth: string } = { sizeWidth: '' };
  isEditStep = true;
  disablePreview = false;
  internalTaskId: string | null = null;
  previewData: Idea[] = [];
  loading = false;
  previewLoading = false;

  TaskType = TaskType;
  ViewType = ViewType;
  IdeaStates = IdeaStates;
  IdeaStateKeys = Object.keys(IdeaStates);
  //#endregion Variables

  //#region Load / Reset / Leave
  taskListCash!: cashService.SimplifiedCashEntry<Task[]>;
  viewCash!: cashService.SimplifiedCashEntry<View[]>;
  @Watch('topicId', { immediate: true })
  onTopicIdChanged(): void {
    this.inputTopicId = this.topicId;
    cashService.deregisterAllGet(this.updateTaskCount);
    cashService.deregisterAllGet(this.updateTopic);
    this.taskListCash = taskService.registerGetTaskList(
      this.topicId,
      this.updateTaskCount,
      EndpointAuthorisationType.MODERATOR,
      60 * 60
    );
    topicService.registerGetTopicById(
      this.topicId,
      this.updateTopic,
      EndpointAuthorisationType.MODERATOR,
      60 * 60
    );
  }

  taskCash!: cashService.SimplifiedCashEntry<Task>;
  @Watch('taskId', { immediate: true })
  async onTaskIdChanged(): Promise<void> {
    cashService.deregisterAllGet(this.updateTask);
    if (this.internalTaskId !== this.taskId) {
      this.loading = true;
      this.internalTaskId = this.taskId;
      if (this.taskId) {
        this.taskCash = taskService.registerGetTaskById(
          this.taskId,
          this.updateTask,
          EndpointAuthorisationType.MODERATOR,
          60 * 60
        );
      } else {
        this.task = null;
        this.mainModule = new ModuleTask('', 'default');
        this.reset();
      }
      this.loading = false;
    }
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateTask);
    cashService.deregisterAllGet(this.updateTaskCount);
    cashService.deregisterAllGet(this.updateModuleName);
    cashService.deregisterAllGet(this.updateInput);
    cashService.deregisterAllGet(this.updateTopic);
    cashService.deregisterAllGet(this.updateTopics);
  }

  mounted(): void {
    this.sortOrderOptions = ideaService.getSortOrderOptions([]);
  }

  unmounted(): void {
    this.deregisterAll();
  }

  inputCash: cashService.SimplifiedCashEntry<Idea[]> | null = null;
  @Watch('internalTaskId', { immediate: true })
  onTaskChanged(): void {
    this.inputCash = null;
    cashService.deregisterAllGet(this.updateInput);
    if (this.internalTaskId) {
      this.inputCash = viewService.registerGetInputIdeas(
        this.internalTaskId,
        IdeaSortOrder.TIMESTAMP,
        null,
        this.updateInput,
        EndpointAuthorisationType.MODERATOR,
        60 * 60 * 24
      );
    }
  }

  updateInput(ideas: Idea[]): void {
    this.previewData = ideas;
    this.previewLoading = false;
  }

  async updateTask(task: Task): Promise<void> {
    this.mainModule = new ModuleTask(task.taskType, 'default');
    this.formData.name = task.name;
    this.formData.description = task.description;
    this.formData.keywords = task.keywords;
    this.formData.parameter = task.parameter ?? {};
    if (this.formData.parameter.input)
      this.formData.input = this.formData.parameter.input.map((input) => {
        return new InputData(
          this.getViewKey(input.view),
          input.maxCount,
          input.filter,
          input.order,
          this.getTopicForView(input.view)
        );
      });
    if (task.modules.length > 1)
      await until(() => this.formData.moduleListAddOn.length > 0);
    task.modules.forEach((module) => {
      const addOn = this.formData.moduleListAddOn.find((item) =>
        item.like(task.taskType, module.name)
      );
      if (addOn) addOn.active = true;
      else this.mainModule = new ModuleTask(task.taskType, module.name);
    });
    this.task = task;
  }

  taskCount = 0;
  //eslint-disable-next-line @typescript-eslint/no-unused-vars
  updateTaskCount(tasks: Task[], topicId: string): void {
    this.taskCount = tasks.length;
  }

  updateTopic(topic: Topic): void {
    if (this.sessionId !== topic.sessionId) {
      this.sessionId = topic.sessionId;
      cashService.deregisterAllGet(this.updateTopics);
      this.viewCash = viewService.registerGetSessionList(
        this.sessionId,
        this.updateTopics,
        EndpointAuthorisationType.MODERATOR,
        2 * 60
      );
    }
  }

  updateTopics(list: View[]): void {
    if (list) {
      this.sessionViews = list.filter(
        (item) =>
          ViewType[item.type] !== ViewType.HIERARCHY ||
          !item.detailType ||
          TaskType[item.detailType] !== TaskType.INFORMATION
      );
    } else {
      this.sessionViews = [];
    }
    this.updateAvailableTopics();
  }

  updateAvailableTopics(): void {
    this.topics = this.possibleSessionViews
      .filter(
        (value, index, array) =>
          array.findIndex((item) => item.topicId === value.topicId) === index
      )
      .map((item) => {
        return {
          id: item.topicId,
          title: item.topicName,
        };
      });
  }

  reset(): void {
    this.previewData = [];
    this.isEditStep = true;
    this.internalTaskId = null;
    this.task = null;
    this.formData.name = '';
    this.formData.description = '';
    this.formData.keywords = '';
    this.formData.parameter = {};
    this.formData.input = [];
    this.formData.moduleListMain = [];
    this.formData.moduleListAddOn = [];
    this.formData.moduleParameterComponents = [];
    this.formData.stateMessage = '';
    this.loadModuleList();
    this.loadParticipantModuleList();
    this.setDefaultInput();
    this.formData.call = ValidationFormCall.CLEAR_VALIDATE;
  }

  handleClose(done: { (): void }): void {
    done();
    this.closeDialog();
  }

  closeDialog(): void {
    this.$emit('update:showModal', false);
    this.resetHideNotUsesModules();
  }
  //#endregion Load / Reset / Leave

  //#region Tutorial
  reactivateTutorial(): void {
    reactivateTutorial('taskSettings', this.eventBus);
  }

  displayModuleTutorial(module: ModuleTask): boolean {
    const displayList = this.displayModuleList;
    const index = displayList.findIndex((item) => item.eq(module));
    if (index === 0) return true;
    else if (index > 0) {
      const addOnList = this.formData.moduleListAddOn;
      const indexAddOn = addOnList.findIndex((item) => item.eq(module));
      if (indexAddOn === 0) return true;
      else if (indexAddOn > 0) {
        const displayItem = displayList[index - 1];
        if (addOnList.findIndex((item) => item.eq(displayItem)) >= 0)
          return true;
      }
    }
    return false;
  }
  //#endregion Tutorial

  //#region Input
  get minAddModuleLength(): number {
    if (this.inputOption == InputOption.OPTIONAL) return 0;
    return 1;
  }

  getEmptyInput(): InputData {
    const possibleViews = this.getPossibleViews();
    return new InputData(
      possibleViews.length > 0 ? this.getViewKey(possibleViews[0]) : '',
      null,
      Object.keys(IdeaStates),
      'order',
      possibleViews.length > 0 ? possibleViews[0].topicId : this.topicId
    );
  }

  getTopicForView(view: { id: string; type: string }): string | null {
    const views = this.sessionViews.filter(
      (item) => item.id === view.id && item.type === view.type
    );
    if (views.length > 0) return views[0].topicId;
    if (this.sessionViews.length > 0) return this.sessionViews[0].topicId;
    return null;
  }

  getInputOptionViews(topicId: string): View[] {
    return this.sessionViews.filter((item) => item.topicId === topicId);
  }

  getPossibleViews(topicId: string | null = null): View[] {
    if (!topicId && this.formData.input.length > 0)
      topicId = this.formData.input[this.formData.input.length - 1].viewTopic;
    else if (
      !topicId &&
      this.possibleSessionViews.find((item) => item.topicId === this.topicId)
    )
      topicId = this.topicId;
    else if (!topicId && this.possibleSessionViews.length > 0)
      topicId = this.possibleSessionViews[0].topicId;
    if (!topicId) return [];
    return this.possibleSessionViews.filter((view) => view.topicId === topicId);
  }

  get possibleSessionViews(): View[] {
    const filterOptions = this.sessionViews.filter(
      (view) =>
        (this.inputFilter.types.length === 0 ||
          this.inputFilter.types.includes(view.type.toLowerCase())) &&
        (this.inputFilter.detailTypes.length === 0 ||
          (view.detailType &&
            this.inputFilter.detailTypes.includes(
              view.detailType.toLowerCase()
            ))) &&
        (this.inputFilter.modules.length === 0 ||
          this.inputFilter.modules.find((item) => {
            if (item.includes(':')) {
              const split = item.split(':');
              return (
                view.detailType &&
                view.detailType.toLowerCase() === split[0] &&
                view.modules.includes(split[1])
              );
            } else {
              return view.modules.includes(item);
            }
          }))
    );
    if (this.internalTaskId)
      return filterOptions.filter(
        (view) => view.taskId !== this.internalTaskId
      );
    return filterOptions;
  }

  addInput(): void {
    this.formData.input.push(this.getEmptyInput());
  }

  deleteInput(input: InputData): void {
    const index = this.formData.input.findIndex((item) => input === item);
    if (index > -1) {
      this.formData.input.splice(index, 1);
    }
  }

  getViewName(view: View): string {
    const $t = (this as any).$t;
    return getViewName(view, $t);
  }

  getViewKey(view: View): string {
    return getViewKey(view);
  }

  async updateInputOption(): Promise<void> {
    let input = InputOption.NO;
    let maxInputs = 20;
    const inputFilter: InputFilter = {
      types: [],
      detailTypes: [],
      modules: [],
    };
    for (const module of this.possibleModuleTaskList) {
      if (module.active) {
        await getModuleConfig(
          'input',
          TaskType[module.taskType.toUpperCase()],
          module.moduleName
        ).then((result) => {
          switch (result) {
            case InputOption.YES:
              input = result;
              break;
            case InputOption.OPTIONAL:
              if (input !== InputOption.YES) input = result;
              break;
          }
        });
        await getModuleConfig(
          'inputFilterTypes',
          TaskType[module.taskType.toUpperCase()],
          module.moduleName
        ).then((result) => {
          if (result) {
            inputFilter.types = JSON.parse(result);
          }
        });
        await getModuleConfig(
          'disablePreview',
          TaskType[module.taskType.toUpperCase()],
          module.moduleName
        ).then((result) => {
          if (result) {
            this.disablePreview = JSON.parse(result);
          } else this.disablePreview = false;
        });
        await getModuleConfig(
          'inputFilterCategories',
          TaskType[module.taskType.toUpperCase()],
          module.moduleName
        ).then((result) => {
          if (result) {
            inputFilter.detailTypes = JSON.parse(result);
          }
        });
        await getModuleConfig(
          'inputFilterModules',
          TaskType[module.taskType.toUpperCase()],
          module.moduleName
        ).then((result) => {
          if (result) {
            inputFilter.modules = JSON.parse(result);
          }
        });
        await getModuleConfig(
          'maxInputs',
          TaskType[module.taskType.toUpperCase()],
          module.moduleName
        ).then((result) => {
          if (result) {
            maxInputs = result;
          }
        });
      }
    }
    this.inputOption = input;
    this.inputFilter = inputFilter;
    this.maxInputs = maxInputs;
    this.setDefaultInput();
    this.updateAvailableTopics();
  }

  setDefaultInput(): void {
    if (
      this.inputOption === InputOption.YES &&
      this.formData.input.length === 0
    ) {
      this.formData.input.push(this.getEmptyInput());
    }
  }

  get showPreview(): boolean {
    return (
      this.inputOption !== InputOption.NO &&
      this.formData.input.length > 0 &&
      !this.disablePreview
    );
  }

  get showInput(): boolean {
    return (
      this.inputOption !== InputOption.NO &&
      this.possibleSessionViews.length > 0
    );
  }

  get inputMinCount(): number {
    return this.inputOption === InputOption.YES ? 1 : 0;
  }
  //#endregion Input

  //#region Modules
  get canActivateModule(): boolean {
    return this.hasParticipantModule && !this.syncPublicParticipant;
  }

  hasModuleScrollbar = false;
  @Watch('moduleScrollbar.sizeWidth')
  onModuleScrollbarChanged(): void {
    if (this.moduleScrollbar) {
      this.hasModuleScrollbar = this.moduleScrollbar.sizeWidth !== '';
    }
  }

  private async loadModuleList(): Promise<void> {
    this.addOneListUnfiltered = [];
    await getModulesForTaskType(this.taskTypes, ModuleType.ADDON).then(
      (result) => {
        result.forEach((module) => {
          const property = ModuleTaskProperty.createFromModuleTask(module);
          if (this.task)
            property.active = this.task.modules.some((moduleTask) =>
              module.like(this.task?.taskType, moduleTask.name)
            );
          this.addOneListUnfiltered.push(property);
        });
      }
    );
    this.formData.moduleListMain = [];
    await getModulesForTaskType(this.taskTypes, ModuleType.MAIN).then(
      (result) => {
        result.forEach((module, moduleIndex) => {
          const property = ModuleTaskProperty.createFromModuleTask(module);
          if (!this.task) property.active = moduleIndex == 0;
          else
            property.active = this.task.modules.some((moduleTask) =>
              module.like(this.task?.taskType, moduleTask.name)
            );
          this.formData.moduleListMain.push(property);
        });
      }
    );
  }

  private async loadParticipantModuleList(): Promise<void> {
    this.participantModuleList = [];
    if (this.possibleModuleTaskList) {
      this.possibleModuleTaskList.forEach((module) => {
        const participantModule = new ModuleTaskProperty(
          module.taskType,
          module.moduleName
        );
        if (participantModule) {
          participantModule.active = false;
          hasModule(
            ModuleComponentType.PARTICIPANT,
            TaskType[module.taskType.toUpperCase()],
            module.moduleName
          ).then((result) => {
            participantModule.active = result;
            this.participantModuleList.push(participantModule);
          });
        }
      });
    }
  }

  @Watch('taskTypes', { immediate: true })
  async onTaskTypesChanged(
    newValue: (keyof typeof TaskType)[],
    oldValue: (keyof typeof TaskType)[]
  ): Promise<void> {
    if (oldValue) {
      const deletedTypes = oldValue.filter(
        (o) => !newValue.find((n) => n === o)
      );
      this.deregisterTaskTypes(deletedTypes);
    }
    const newTypes = oldValue
      ? newValue.filter((n) => !oldValue.find((o) => n === o))
      : newValue;
    newTypes.forEach((type) => {
      moduleService.registerGetUsedModuleNames(
        type,
        this.updateModuleName,
        60 * 60
      );
    });

    this.hideNotUsesModules = false;
    await this.loadModuleList();
    const mainModules = this.getIntersection(
      this.userModuleList,
      this.formData.moduleListMain
    );
    if (!this.internalTaskId) {
      if (mainModules.length > 0) {
        this.mainModule = mainModules[0];
      }
    }
  }

  updateModuleName(typeModules: string[], taskType: string): void {
    for (const moduleName of typeModules)
      this.userModuleList.push(new ModuleTask(taskType, moduleName));
  }

  deregisterTaskTypes(types: (keyof typeof TaskType)[]): void {
    types.forEach((type) => {
      moduleService.deregisterGetUsedModuleNames(type, this.updateModuleName);
    });
  }

  @Watch('taskType', { immediate: true })
  async onTaskTypeChanged(): Promise<void> {
    if (this.taskType) {
      this.formData.moduleListAddOn = this.addOneListUnfiltered.filter(
        (item) => item.taskType.toUpperCase() == this.taskType
      );
      await this.loadParticipantModuleList();

      if (this.$options.components) {
        getAsyncTaskParameter(TaskType[this.taskType]).then((component) => {
          if (this.$options.components)
            this.$options.components['TaskParameterComponent'] = component;
          this.componentLoadIndex++;
        });
      }

      this.resetHideNotUsesModules();
    }
  }

  private resetHideNotUsesModules(): void {
    this.hideNotUsesModules = false;

    const mainModules = this.getIntersection(
      this.userModuleList,
      this.formData.moduleListMain
    );
    const addOnModules = this.getIntersection(
      this.userModuleList,
      this.formData.moduleListAddOn
    );
    this.usedModuleList = [...mainModules, ...addOnModules];
    this.hideNotUsesModules =
      mainModules.length > 0 &&
      this.userModuleList.length < this.possibleModuleTaskList.length;
  }

  private getIntersection(
    usedModules: ModuleTask[],
    modules: ModuleTaskProperty[]
  ): ModuleTaskProperty[] {
    return modules.filter((value) =>
      usedModules.find((item) => item.eq(value))
    );
  }

  get mainModule(): ModuleTask {
    if (this.moduleSelectionMain.length > 0) return this.moduleSelectionMain[0];
    return ModuleTask.createEmpty();
  }

  set mainModule(value: ModuleTask) {
    const item = this.formData.moduleListMain.find((item) => item.eq(value));
    if (item) {
      for (const obj of this.formData.moduleListMain) {
        obj.active = false;
      }
      item.active = true;
    }
  }

  get taskType(): keyof typeof TaskType | undefined {
    const taskType = TaskType[this.mainModule.taskType.toUpperCase()];
    if (taskType) return taskType.toUpperCase();
    return taskType;
  }

  validateModuleSelection(
    rule: ValidationRules,
    value: string,
    // eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
    callback: any
  ): boolean {
    if (this.moduleSelectionMain.length > 0) {
      callback();
      return true;
    } else {
      callback(new Error((this as any).$t('error.vuelidate.required')));
      return false;
    }
  }

  showDialog = false;
  @Watch('showModal', { immediate: false, flush: 'post' })
  async onShowModalChanged(showModal: boolean): Promise<void> {
    this.sortOrderOptions = ideaService.getSortOrderOptions([]);
    this.showDialog = showModal;

    if (showModal) {
      if (this.viewCash) this.viewCash.refreshData();
      this.isEditStep = true;
      this.onTaskIdChanged();
      setTimeout(() => {
        this.moduleScrollbar = this.$refs.moduleScrollbar as {
          sizeWidth: string;
        };
      }, 1000);
    } else {
      this.reset();
    }
  }

  get possibleModuleTaskList(): ModuleTaskProperty[] {
    return [...this.formData.moduleListMain, ...this.formData.moduleListAddOn];
  }

  get displayModuleList(): ModuleTaskProperty[] {
    let moduleNames = this.hideNotUsesModules
      ? this.usedModuleList
      : this.possibleModuleTaskList;
    if (this.moduleSearch) {
      moduleNames = moduleNames.filter((module) => {
        const name = (this as any).$t(
          `module.${module.taskType.toLowerCase()}.${
            module.moduleName
          }.description.title`
        );
        return name.toLowerCase().includes(this.moduleSearch.toLowerCase());
      });
    }
    if (this.task) {
      moduleNames = moduleNames.filter(
        (module) =>
          module.active ||
          this.formData.moduleListAddOn.find(
            (item) =>
              item.moduleName === module.moduleName &&
              item.taskType === module.taskType
          )
      );
    }
    return moduleNames;
  }

  get useModuleFilter(): boolean {
    return this.possibleModuleTaskList.length >= 8;
  }

  get moduleList(): ModuleTaskProperty[] {
    return [...this.formData.moduleListMain, ...this.formData.moduleListAddOn];
  }

  @Watch('moduleList', { immediate: true, deep: true })
  async onModuleListChanged(): Promise<void> {
    await this.updateInputOption();
  }

  get moduleSelectionMain(): ModuleTask[] {
    return this.formData.moduleListMain
      .filter((item) => item.active)
      .map((item) => item);
  }

  get moduleSelectionAddOn(): ModuleTask[] {
    return this.formData.moduleListAddOn
      .filter((item) => item.active)
      .map((item) => item);
  }

  get moduleSelection(): ModuleTask[] {
    return [...this.moduleSelectionMain, ...this.moduleSelectionAddOn];
  }

  removeModuleType(moduleTask: ModuleTask): void {
    const addOn = this.formData.moduleListAddOn.find((item) =>
      item.eq(moduleTask)
    );
    if (addOn) addOn.active = false;
    const main = this.formData.moduleListMain.find((item) =>
      item.eq(moduleTask)
    );
    if (main) main.active = false;
  }

  @Watch('moduleSelection', { immediate: true })
  async onModuleSelectionChanged(): Promise<void> {
    const oldKeys = this.formData.moduleParameterComponents.map(
      (data) => data.componentName
    );

    const addComponent = async (
      taskType: string,
      moduleName: string,
      componentName: string
    ): Promise<void> => {
      let moduleId: string | null = null;
      let moduleParameter: any = {};
      if (this.task) {
        const componentModule = this.task.modules.find(
          (module) => module.name == moduleName
        );
        if (componentModule) {
          moduleId = componentModule.id;
          moduleParameter = componentModule.parameter;
        }
      }

      let icon: string[] | null = null;
      await getModuleConfig('icon', TaskType[taskType], moduleName).then(
        (result) => {
          getModuleConfig('iconPrefix', TaskType[taskType], moduleName).then(
            (iconPrefix) => {
              icon = [iconPrefix, result];
            }
          );
        }
      );

      hasModule(
        ModuleComponentType.MODERATOR_CONFIG,
        TaskType[taskType],
        moduleName
      ).then((result) => {
        if (
          !this.formData.moduleParameterComponents.find(
            (component) => component.componentName === componentName
          )
        ) {
          this.formData.moduleParameterComponents.push({
            componentName: componentName,
            moduleId: moduleId,
            moduleName: moduleName,
            taskType: taskType,
            moduleIcon: icon,
            hasModule: result,
            parameter: moduleParameter,
          });
          if (
            !this.openTabs.includes(componentName) &&
            !oldKeys.includes(componentName)
          )
            this.openTabs.push(componentName);
        }
      });

      this.updateSyncPublicParticipant();
    };

    if (this.$options.components) {
      this.formData.moduleParameterComponents = [];
      this.moduleSelection.forEach((module) => {
        const componentName = `ModuleParameterComponents-${
          TaskType[module.taskType.toUpperCase()]
        }-${module.moduleName}`;
        if (
          this.$options.components &&
          !this.$options.components[componentName]
        ) {
          getAsyncModule(
            ModuleComponentType.MODERATOR_CONFIG,
            TaskType[module.taskType.toUpperCase()],
            module.moduleName
          ).then((component) => {
            if (this.$options.components)
              this.$options.components[componentName] = component;
            addComponent(
              module.taskType.toUpperCase(),
              module.moduleName,
              componentName
            );
          });
        } else {
          addComponent(
            module.taskType.toUpperCase(),
            module.moduleName,
            componentName
          );
        }
      });
    }
  }

  updateSyncPublicParticipant(): void {
    if (this.taskType) {
      const taskType = TaskType[this.taskType];
      const moduleName = this.mainModule.moduleName;
      this.getStaticSyncProperty(taskType, moduleName).then((result) => {
        this.getCustomSyncProperty(taskType, moduleName).then(
          (customResult) => {
            if (customResult !== undefined)
              this.syncPublicParticipant = !!result && !!customResult;
            else this.syncPublicParticipant = !!result;
          }
        );
      });
    }
  }

  participantModuleList: ModuleTaskProperty[] = [];
  get hasParticipantModule(): boolean {
    let hasParticipantModule = false;
    this.moduleSelection.forEach((module) => {
      if (
        this.participantModuleList.find(
          (item) => item.eq(module) && item.active
        )
      )
        hasParticipantModule = true;
    });
    return hasParticipantModule;
  }
  //#endregion Modules

  //#region Save
  async confirmAndActivate(): Promise<void> {
    if (this.task) this.task.state = TaskStates.ACTIVE;
    await this.save(TaskStates.ACTIVE).then((task) => {
      this.$emit('showTimerSettings', task);
      this.confirm();
    });
  }

  async saveAndActivate(): Promise<void> {
    if (this.task) this.task.state = TaskStates.ACTIVE;
    await this.save(TaskStates.ACTIVE).then((task) => {
      this.$emit('showTimerSettings', task);
    });
  }

  isSaving = false;
  async save(state: TaskStates = TaskStates.WAIT): Promise<Task | null> {
    let saveTask: Task | null = this.task;
    this.isSaving = true;
    await this.updateCustomTaskParameter();
    this.formData.parameter.input = this.formData.input.map((input) => {
      const view = this.sessionViews.find(
        (view) =>
          this.getViewKey(view) === input.view &&
          view.topicId === input.viewTopic
      );
      if (view)
        return {
          view: {
            type: view.type,
            id: view.id,
          },
          maxCount: input.maxCount,
          filter: input.filter,
          order: input.order,
        };
      return {};
    });
    this.formData.parameter.input = this.formData.parameter.input.filter(
      (input) => Object.keys(input).length > 0
    );

    if (this.internalTaskId) {
      this.previewLoading = true;
      await taskService
        .putTask({
          id: this.internalTaskId,
          taskType: this.taskType,
          name: this.formData.name,
          description: this.formData.description,
          keywords: this.formData.keywords,
          parameter: this.formData.parameter,
          modules: this.moduleSelection.map((item) => item.moduleName),
          state: this.task?.state,
          order: this.task?.order,
          dependency: this.task?.dependency,
        })
        .then(
          (task) => {
            if (this.inputCash) this.inputCash.refreshData(false);
            saveTask = task;
            this.taskUpdated(task);
            if (this.taskCash) this.taskCash.refreshData();
            this.taskListCash.refreshData();
          },
          (error) => {
            this.previewLoading = false;
            this.formData.stateMessage = getSingleTranslatedErrorMessage(error);
          }
        );
    } else if (this.topicId) {
      this.previewLoading = true;
      await taskService
        .postTask(this.topicId, {
          taskType: this.taskType,
          name: this.formData.name,
          description: this.formData.description,
          keywords: this.formData.keywords,
          parameter: this.formData.parameter,
          state: state,
          order: this.taskCount,
          modules: this.moduleSelection.map((item) => item.moduleName),
        })
        .then(
          (task) => {
            saveTask = task;
            this.task = task;
            this.internalTaskId = task.id;
            this.taskUpdated(task);
            this.taskListCash.refreshData();
            this.initCreationData(task.id);
          },
          (error) => {
            this.previewLoading = false;
            this.formData.stateMessage = getSingleTranslatedErrorMessage(error);
          }
        );
    }
    this.isSaving = false;
    if (this.showPreview) this.isEditStep = false;
    return saveTask;
  }

  confirm(): void {
    this.closeDialog();
  }

  async updateCustomTaskParameter(): Promise<void> {
    if (this.$refs.taskParameter) {
      const params = this.$refs.taskParameter as CustomParameter;
      if ('updateParameterForSaving' in params)
        await params.updateParameterForSaving();
    }
  }

  async initCreationData(taskId: string): Promise<void> {
    for (const component of this.formData.moduleParameterComponents) {
      if (this.$refs[component.componentName]) {
        const paramList = this.$refs[component.componentName] as [CustomInit];
        for (const params of paramList) {
          if ('initCreationData' in params)
            await params.initCreationData(taskId);
        }
      }
    }
  }

  async updateCustomModuleParameter(task: Task, module: Module): Promise<void> {
    await this.getCustomSyncProperty(
      TaskType[task.taskType.toUpperCase()],
      module.name
    ).then((result) => {
      if (result !== undefined && result !== null)
        module.syncPublicParticipant = result;
    });
  }

  async getCustomSyncProperty(
    taskType: TaskType,
    moduleName: string
  ): Promise<boolean | undefined | null> {
    let syncPublicParticipant: boolean | undefined = undefined;
    const componentName = `ModuleParameterComponents-${taskType}-${moduleName}`;
    if (this.$refs[componentName]) {
      let moduleParams = this.$refs[componentName] as CustomParameter;
      if (Array.isArray(moduleParams) && moduleParams.length > 0) {
        moduleParams = moduleParams[moduleParams.length - 1] as CustomParameter;
      }
      if ('updateParameterForSaving' in moduleParams)
        await moduleParams.updateParameterForSaving();
      if ('customSyncPublicParticipant' in moduleParams) {
        syncPublicParticipant = (
          moduleParams as unknown as CustomSync
        ).customSyncPublicParticipant();
      }
    }
    return syncPublicParticipant;
  }

  async getStaticSyncProperty(
    taskType: TaskType,
    moduleName: string
  ): Promise<boolean | null> {
    let syncPublicParticipant = false;
    await getModuleConfig(
      'syncPublicParticipant',
      taskType,
      moduleName,
      false
    ).then((result) => {
      syncPublicParticipant = result;
    });
    return syncPublicParticipant;
  }

  async taskUpdated(task: Task): Promise<void> {
    for (const module of task.modules) {
      await this.getStaticSyncProperty(
        TaskType[task.taskType],
        module.name
      ).then((result) => {
        if (result !== null) module.syncPublicParticipant = result;
      });
      await this.updateCustomModuleParameter(task, module);
      const moduleComponent = this.formData.moduleParameterComponents.find(
        (component) => component.moduleName == module.name
      );
      if (moduleComponent) {
        module.parameter = moduleComponent.parameter;
      }
      await moduleService.putModule(module);
    }
    if (!this.showPreview) {
      this.closeDialog();
    }
    this.$emit('taskUpdated', task);
    this.eventBus.emit(EventType.CHANGE_SETTINGS, task.id);
  }
  //#endregion Save
}
</script>

<style lang="scss" scoped>
.el-collapse-item::v-deep(.el-collapse-item__content) {
  padding-bottom: 0;
}

.input-table {
  width: 100%;

  td,
  th {
    padding: 0.5rem;
  }
}

.el-scrollbar {
  max-width: calc(var(--app-width) * 0.8);
}

.flex-content {
  display: flex;
  margin-bottom: 1rem;

  .el-card {
    flex-shrink: 0;
    width: 15rem;
    margin: 0 0.5rem;
  }
}

.showMore {
  color: var(--color-highlight-dark);
  border-color: var(--color-highlight-dark);
  //background-color: var(--color-highlight-light);
  cursor: pointer;
  height: unset;
  //height: 100%;
}

.search.el-input {
  margin-bottom: 1rem;
}

.search.el-input::v-deep(.el-input__inner) {
  font-size: var(--font-size-small);
  font-style: italic;
  color: var(--color-gray-dark);
  border-color: var(--color-gray);
}

.search.el-input::v-deep(.el-input__suffix) {
  color: var(--color-gray-dark);
}

.el-input-number {
  width: 140px;
}

.select--fullwidth {
  width: 100%;
}

.add::v-deep(.el-card__body) {
  min-height: unset;
  padding: 0.5rem;
}

.level:not(:last-child) {
  margin-bottom: 0;
}

.el-dialog__footer {
  .level-item {
    width: 10rem;
    max-width: 100%;

    .el-button {
      margin: 0;
    }
  }

  .level-item:not(:last-child) {
    margin-right: 1rem;
  }
}

@media screen and (max-width: 768px) {
  .level-left + .level-right {
    margin-top: 0;
  }
}

.module-selection {
  .level-right {
    min-width: 50%;
  }
}

.el-collapse {
  margin-bottom: 0;
}

.el-collapse::v-deep(.el-collapse-item__header) {
  padding-left: 0;
}

.layout__columns {
  width: calc(
    calc(var(--app-width) * 0.8) - 2 * var(--el-dialog-padding-primary)
  );
}
</style>
