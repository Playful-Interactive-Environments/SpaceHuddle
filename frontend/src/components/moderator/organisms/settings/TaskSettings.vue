<template>
  <div v-if="showDialog">
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
        width="80vw"
      >
        <template #title>
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
        </template>
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
              <col />
              <col style="width: 10rem" />
              <col style="width: 12rem" />
              <col style="width: 12rem" />
              <col />
            </colgroup>
            <thead>
              <tr>
                <TutorialStep
                  step="inputSource"
                  type="taskSettings"
                  :order="1"
                  :displayAllDuplicates="true"
                >
                  <th>
                    {{ $t('moderator.organism.settings.taskSettings.source') }}
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
                <TutorialStep
                  step="inputFilter"
                  type="taskSettings"
                  :order="3"
                  :displayAllDuplicates="true"
                >
                  <th>
                    {{ $t('moderator.organism.settings.taskSettings.filter') }}
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
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="input in formData.input" :key="input.view">
                <td>
                  <el-select v-model="input.view" class="select--fullwidth">
                    <el-option
                      v-for="view in possibleViews"
                      :key="view"
                      :value="getViewKey(view)"
                      :label="getViewName(view)"
                    >
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
                  <select
                    v-model="input.order"
                    class="select select--fullwidth"
                  >
                    <option
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
                    </option>
                  </select>
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
            v-if="possibleViews.length > 1 && formData.input.length < 20"
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
          <el-input
            type="textarea"
            v-model="formData.description"
            rows="3"
            :placeholder="
              $t('moderator.organism.settings.taskSettings.descriptionExample')
            "
          />
        </el-form-item>
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
                {{ $t('moderator.organism.settings.taskSettings.moduleType') }}
              </span>
            </TutorialStep>
          </template>
          <div>
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
                $t('moderator.organism.settings.taskSettings.moduleSearch')
              "
              v-if="possibleModuleTaskList.length > 1"
            >
              <template #suffix>
                <font-awesome-icon icon="search" />
              </template>
            </el-input>
          </TutorialStep>
          <el-scrollbar>
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
        <TaskParameterComponent
          ref="taskParameter"
          :taskId="taskId"
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
              :is="component.value.componentName"
              :key="component.value.componentName"
              :rulePropPath="`moduleParameterComponents[${component.index}].parameter`"
            ></component>
          </el-collapse-item>
        </el-collapse>
        <template #footer>
          <FromSubmitItem
            :form-state-message="formData.stateMessage"
            submit-label-key="moderator.organism.settings.taskSettings.submit"
            :disabled="isSaving"
          />
          <el-button
            type="primary"
            v-on:click="saveAndActivate"
            v-if="hasParticipantModule"
          >
            {{ $t('moderator.organism.settings.taskSettings.saveAndActivate') }}
          </el-button>
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
import { CustomParameter } from '@/types/ui/CustomParameter';
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
import { View } from '@/types/api/View';
import IdeaStates from '@/types/enum/IdeaStates';
import { SortOrderOption } from '@/types/api/OrderGroup';
import * as ideaService from '@/services/idea-service';
import AddItem from '@/components/moderator/atoms/AddItem.vue';
import ViewType from '@/types/enum/ViewType';
import TutorialStep from '@/components/shared/atoms/TutorialStep.vue';
import { reactivateTutorial } from '@/services/auth-service';

/* eslint-disable @typescript-eslint/no-explicit-any*/

interface ModuleComponentDefinition {
  componentName: string;
  taskType: string | null;
  moduleName: string | null;
  moduleIcon: string | null;
  moduleId: string | null;
  hasModule: boolean;
  parameter: any;
}

class InputData {
  view = '';
  maxCount: number | null = null;
  filter: string[] = [];
  order = '';

  oldMaxCount = 10;

  constructor(
    view: string,
    maxCount: number | null,
    filter: string[],
    order: string
  ) {
    this.view = view;
    this.maxCount = maxCount;
    this.filter = filter;
    this.order = order;
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
    this.maxCount = this.maxCount ? this.maxCount : null;
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
  parameter: any;
  input: InputData[];
  moduleListMain: ModuleTaskProperty[];
  moduleListAddOn: ModuleTaskProperty[];
  moduleParameterComponents: ModuleComponentDefinition[];
  stateMessage: string;
  call: string;
}

enum InputOption {
  YES = 'yes',
  NO = 'no',
  OPTIONAL = 'optional',
}

@Options({
  components: {
    TutorialStep,
    TimerSettings,
    ValidationForm,
    FromSubmitItem,
    ModuleCard,
    AddItem,
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

  componentLoadIndex = 0;
  usedModuleList: ModuleTaskProperty[] = [];
  userModuleList: ModuleTask[] = [];
  addOneListUnfiltered: ModuleTaskProperty[] = [];
  hideNotUsesModules = true;
  moduleSearch = '';

  formData: FormDataDefinition = {
    input: [],
    name: '',
    description: '',
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
  inputOptionViews: View[] = [];
  sortOrderOptions: SortOrderOption[] = [];

  TaskType = TaskType;
  ViewType = ViewType;
  IdeaStates = IdeaStates;
  IdeaStateKeys = Object.keys(IdeaStates);
  //#endregion Variables

  //#region Load / Reset / Leave
  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    if (this.taskId) {
      taskService.getTaskById(this.taskId).then((task) => {
        this.mainModule = new ModuleTask(task.taskType, 'default');
        this.formData.name = task.name;
        this.formData.description = task.description;
        this.formData.parameter = task.parameter ?? {};
        if (this.formData.parameter.input)
          this.formData.input = this.formData.parameter.input.map((input) => {
            return new InputData(
              this.getViewKey(input.view),
              input.maxCount,
              input.filter,
              input.order
            );
          });
        task.modules.forEach((module) => {
          const addOn = this.formData.moduleListAddOn.find((item) =>
            item.like(task.taskType, module.name)
          );
          if (addOn) addOn.active = true;
          else this.mainModule = new ModuleTask(task.taskType, module.name);
        });
        this.task = task;
      });
    } else {
      this.task = null;
      this.mainModule = new ModuleTask('', 'default');
      this.reset();
    }
  }

  reset(): void {
    this.task = null;
    this.loadModuleList();
    this.loadParticipantModuleList();
    this.formData.name = '';
    this.formData.description = '';
    this.formData.parameter = {};
    this.formData.input = [];
    this.setDefaultInput();
    this.formData.call = ValidationFormCall.CLEAR_VALIDATE;
  }

  handleClose(done: { (): void }): void {
    if (!this.taskId) this.reset();
    done();
    this.closeDialog();
  }

  closeDialog(): void {
    this.$emit('update:showModal', false);
    this.loadUserModuleList().then(() => {
      this.resetHideNotUsesModules();
    });
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
  getEmptyInput(): InputData {
    return new InputData(
      this.possibleViews.length > 0
        ? this.getViewKey(this.possibleViews[0])
        : '',
      null,
      Object.keys(IdeaStates),
      'order'
    );
  }

  get possibleViews(): View[] {
    if (this.taskId)
      return this.inputOptionViews.filter(
        (view) => view.taskId !== this.taskId
      );
    return this.inputOptionViews;
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
    const type = $t(`enum.viewType.${ViewType[view.type]}`);
    const detailType = view.detailType
      ? ' - ' + $t(`enum.taskType.${TaskType[view.detailType]}`)
      : '';
    return `${type}${detailType} - ${view.name}`;
  }

  getViewKey(view: View): string {
    return `${view.type}.${view.id}`;
  }

  async updateInputOption(): Promise<void> {
    let input = InputOption.NO;
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
      }
    }
    this.inputOption = input;
    this.setDefaultInput();
  }

  setDefaultInput(): void {
    if (
      this.inputOption === InputOption.YES &&
      this.formData.input.length === 0
    ) {
      this.formData.input.push(this.getEmptyInput());
    }
  }

  get showInput(): boolean {
    return this.inputOption !== InputOption.NO;
  }

  get inputMinCount(): number {
    return this.inputOption === InputOption.YES ? 1 : 0;
  }

  @Watch('topicId', { immediate: true })
  @Watch('showModal', { immediate: true })
  onTopicIdChanged(): void {
    this.loadInputViews();
    ideaService.getSortOrderOptions(this.topicId).then((options) => {
      this.sortOrderOptions = options;
    });
  }

  async loadInputViews(): Promise<void> {
    viewService.getList(this.topicId).then((views) => {
      this.inputOptionViews = views.filter(
        (item) =>
          ViewType[item.type] !== ViewType.HIERARCHY ||
          !item.detailType ||
          TaskType[item.detailType] !== TaskType.INFORMATION
      );
    });
  }
  //#endregion Input

  //#region Modules
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

  private async loadUserModuleList(): Promise<void> {
    this.userModuleList = [];
    for (const taskType of this.taskTypes) {
      await moduleService.getUsedModuleNames(taskType).then((typeModules) => {
        for (const moduleName of typeModules)
          this.userModuleList.push(new ModuleTask(taskType, moduleName));
      });
    }
  }

  @Watch('taskTypes', { immediate: true })
  async onTaskTypesChanged(): Promise<void> {
    this.hideNotUsesModules = false;
    await this.loadUserModuleList();
    await this.loadModuleList();
    const mainModules = this.getIntersection(
      this.userModuleList,
      this.formData.moduleListMain
    );
    if (!this.taskId) {
      if (mainModules.length > 0) {
        this.mainModule = mainModules[0];
      }
    }
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
    this.showDialog = showModal;
  }

  get possibleModuleTaskList(): ModuleTaskProperty[] {
    return [...this.formData.moduleListMain, ...this.formData.moduleListAddOn];
  }

  get displayModuleList(): ModuleTaskProperty[] {
    const moduleNames = this.hideNotUsesModules
      ? this.usedModuleList
      : this.possibleModuleTaskList;
    if (this.moduleSearch) {
      return moduleNames.filter((module) => {
        const name = (this as any).$t(
          `module.${module.taskType.toLowerCase()}.${
            module.moduleName
          }.description.title`
        );
        return name.toLowerCase().includes(this.moduleSearch.toLowerCase());
      });
    }
    return moduleNames;
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

      let icon: string | null = null;
      await getModuleConfig('icon', TaskType[taskType], moduleName).then(
        (result) => (icon = result)
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
    };

    if (this.$options.components) {
      this.formData.moduleParameterComponents = [];
      this.moduleSelection.forEach((module) => {
        const componentName = `ModuleParameterComponents-${module.taskType}-${module.moduleName}`;
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
    //this.componentLoadIndex++;
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
      const view = this.inputOptionViews.find(
        (view) => this.getViewKey(view) === input.view
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
      (input) => input !== {}
    );

    if (this.taskId) {
      await taskService
        .putTask(this.taskId, {
          taskType: this.taskType,
          name: this.formData.name,
          description: this.formData.description,
          parameter: this.formData.parameter,
          modules: this.moduleSelection.map((item) => item.moduleName),
          state: this.task?.state,
          order: this.task?.order,
        })
        .then(
          (task) => {
            this.taskUpdated(task, false);
          },
          (error) => {
            this.formData.stateMessage = getSingleTranslatedErrorMessage(error);
          }
        );
    } else if (this.topicId) {
      let taskCount = 0;
      await taskService.getTaskList(this.topicId).then((tasks) => {
        taskCount = tasks.length;
      });

      await taskService
        .postTask(this.topicId, {
          taskType: this.taskType,
          name: this.formData.name,
          description: this.formData.description,
          parameter: this.formData.parameter,
          state: state,
          order: taskCount,
          modules: this.moduleSelection.map((item) => item.moduleName),
        })
        .then(
          (task) => {
            saveTask = task;
            this.taskUpdated(task, true);
          },
          (error) => {
            this.formData.stateMessage = getSingleTranslatedErrorMessage(error);
          }
        );
    }
    this.isSaving = false;
    return saveTask;
  }

  async updateCustomTaskParameter(): Promise<void> {
    if (this.$refs.taskParameter) {
      const params = this.$refs.taskParameter as CustomParameter;
      if ('updateParameterForSaving' in params)
        await params.updateParameterForSaving();
    }
  }

  async updateCustomModuleParameter(task: Task, module: Module): Promise<void> {
    const componentName = `ModuleParameterComponents-${task.taskType}-${module.name}`;
    if (this.$refs[componentName]) {
      const moduleParams = this.$refs[componentName] as CustomParameter;
      if ('updateParameterForSaving' in moduleParams)
        await moduleParams.updateParameterForSaving();
    }
  }

  async taskUpdated(task: Task, cleanUp = true): Promise<void> {
    task.modules.forEach((module) => {
      this.updateCustomModuleParameter(task, module).then(() => {
        const moduleComponent = this.formData.moduleParameterComponents.find(
          (component) => component.moduleName == module.name
        );
        if (moduleComponent) {
          module.parameter = moduleComponent.parameter;
        }
        getModuleConfig(
          'syncPublicParticipant',
          TaskType[task.taskType],
          module.name,
          false
        ).then((result) => {
          module.syncPublicParticipant = result;
          moduleService.putModule(module.id, module);
        });
      });
    });
    this.closeDialog();
    this.$emit('taskUpdated', task.id);
    if (cleanUp) this.reset();
    this.eventBus.emit(EventType.CHANGE_SETTINGS, task.id);
  }
  //#endregion Save
}
</script>

<style lang="scss" scoped>
.el-collapse-item::v-deep .el-collapse-item {
  &__content {
    padding-bottom: 0;
  }
}

.el-button {
  width: 100%;
}

.input-table {
  width: 100%;

  td,
  th {
    padding: 0.5rem;
  }
}

.el-select::v-deep {
  .el-select__tags > span {
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
  }
}

.el-scrollbar {
  max-width: 80vw;
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
  color: var(--color-purple-dark);
  border-color: var(--color-purple-dark);
  //background-color: var(--color-purple-light);
  cursor: pointer;
  height: unset;
  //height: 100%;
}

.el-input::v-deep {
  &.search {
    margin-bottom: 1rem;

    .el-input__inner {
      font-size: var(--font-size-small);
      font-style: italic;
      color: var(--color-gray-dark);
      border-color: var(--color-gray);
    }

    .el-input__suffix {
      color: var(--color-gray-dark);
    }
  }
}

.el-input-number {
  width: 140px;
}
</style>
