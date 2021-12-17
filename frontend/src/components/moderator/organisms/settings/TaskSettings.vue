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
          <span class="el-dialog__title">{{
            $t('moderator.organism.settings.taskSettings.header')
          }}</span>
          <br />
          <br />
          <p>
            {{ $t('moderator.organism.settings.taskSettings.info') }}
          </p>
        </template>
        <el-form-item
          v-if="!taskType"
          :label="$t('moderator.organism.settings.taskSettings.taskType')"
          prop="taskType"
          :rules="[defaultFormRules.ruleSelection]"
        >
          <el-select v-model="formData.taskType" class="select--fullwidth">
            <el-option
              v-for="type in TaskTypeKeys"
              :key="type"
              :value="type"
              :label="$t(`enum.taskType.${TaskType[type]}`)"
            >
            </el-option>
          </el-select>
        </el-form-item>
        <el-form-item
          v-if="showInput"
          :label="$t('moderator.organism.settings.taskSettings.input')"
        >
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
                  :order="0"
                  :displayAllDuplicates="true"
                >
                  <th>
                    {{ $t('moderator.organism.settings.taskSettings.source') }}
                  </th>
                </TutorialStep>
                <TutorialStep
                  step="inputMaxCount"
                  type="taskSettings"
                  :order="1"
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
                  :order="2"
                  :displayAllDuplicates="true"
                >
                  <th>
                    {{ $t('moderator.organism.settings.taskSettings.filter') }}
                  </th>
                </TutorialStep>
                <TutorialStep
                  step="inputOrder"
                  type="taskSettings"
                  :order="3"
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
            :order="4"
            :displayAllDuplicates="true"
          >
            <AddItem
              :text="$t('moderator.organism.settings.taskSettings.addInput')"
              @addNew="addInput"
            />
          </TutorialStep>
        </el-form-item>
        <TaskParameterComponent
          ref="taskParameter"
          :taskId="taskId"
          :topicId="topicId"
          v-model="formData"
        />
        <el-form-item
          :label="
            formData.taskType === 'BRAINSTORMING'
              ? $t('moderator.organism.settings.taskSettings.question')
              : $t('moderator.organism.settings.taskSettings.title')
          "
          prop="name"
          :rules="[
            defaultFormRules.ruleRequired,
            defaultFormRules.ruleToLong(255),
          ]"
        >
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
          :label="$t('moderator.organism.settings.taskSettings.moduleType')"
          prop="moduleListMain"
          :rules="[
            defaultFormRules.ruleSelection,
            { validator: validateModuleSelection },
          ]"
          v-if="moduleKeyList.length > 1"
        >
          <div>
            <el-tag v-for="tag in moduleSelectionMain" :key="tag">
              {{
                $t(
                  `module.${
                    TaskType[formData.taskType]
                  }.${tag}.description.title`
                )
              }}
            </el-tag>
            <el-tag
              v-for="tag in moduleSelectionAddOn"
              :key="tag"
              :closable="true"
              v-on:close="removeModuleType(tag)"
            >
              {{
                $t(
                  `module.${
                    TaskType[formData.taskType]
                  }.${tag}.description.title`
                )
              }}
            </el-tag>
          </div>
          <el-input
            class="search"
            v-model="moduleSearch"
            :clearable="true"
            :placeholder="
              $t('moderator.organism.settings.taskSettings.moduleSearch')
            "
            v-if="moduleKeyList.length > 1"
          >
            <template #suffix>
              <font-awesome-icon icon="search" />
            </template>
          </el-input>
          <el-scrollbar>
            <div class="flex-content">
              <!--<p v-for="item in 50" :key="item" class="scrollbar-demo-item">
                {{ item }}
              </p>-->
              <ModuleCard
                v-for="moduleType in displayModuleList"
                :key="moduleType"
                :task-type="TaskType[formData.taskType]"
                :moduleName="moduleType"
                v-model:mainModule="mainModule"
                v-model="formData.moduleListAddOn[moduleType]"
              />
              <IdeaCard
                v-if="hideNotUsesModules"
                :idea="{ keywords: '...' }"
                :is-editable="false"
                :show-state="false"
                v-on:click="hideNotUsesModules = false"
                class="showMore"
              />
            </div>
          </el-scrollbar>
          <!--<el-carousel
            id="moduleType"
            :autoplay="false"
            type="card"
            arrow="always"
            height="250px"
            v-if="moduleKeyList.length > 1"
            trigger="click"
          >
            <el-carousel-item
              v-for="moduleType in moduleKeyList"
              :key="moduleType"
            >
              <ModuleCard
                :task-type="TaskType[formData.taskType]"
                :moduleName="moduleType"
                v-model:mainModule="mainModule"
                v-model="formData.moduleListAddOn[moduleType]"
              />
            </el-carousel-item>
          </el-carousel>-->
        </el-form-item>
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
                    `module.${TaskType[formData.taskType]}.${
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
import { Task, TaskSettingsData } from '@/types/api/Task';
import TaskStates from '@/types/enum/TaskStates';
import { getSingleTranslatedErrorMessage } from '@/services/exception-service';
import {
  getAsyncModule,
  getAsyncTaskParameter,
  getEmptyComponent,
  getModuleConfig,
  getModulesForTaskType,
  hasModule,
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
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import TutorialStep from '@/components/shared/atoms/TutorialStep.vue';

/* eslint-disable @typescript-eslint/no-explicit-any*/

interface ModuleComponentDefinition {
  componentName: string;
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

interface FormDataDefinition extends TaskSettingsData {
  input: InputData[];
  moduleListMain: { [key: string]: boolean };
  moduleListAddOn: { [key: string]: boolean };
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
    IdeaCard,
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
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;

  @Prop({ default: false }) showModal!: boolean;
  @Prop({}) topicId!: string;
  @Prop({}) taskId!: string;
  @Prop({}) taskType!: keyof typeof TaskType;
  componentLoadIndex = 0;
  usedModuleNames: string[] = [];
  hideNotUsesModules = true;
  moduleSearch = '';

  get TaskTypeKeys(): Array<keyof typeof TaskType> {
    return Object.keys(TaskType) as Array<keyof typeof TaskType>;
  }

  formData: FormDataDefinition = {
    taskType: (Object.keys(TaskType) as Array<keyof typeof TaskType>)[1],
    input: [],
    name: '',
    description: '',
    parameter: {},
    moduleListMain: {},
    moduleListAddOn: {},
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

  @Watch('taskType', { immediate: true })
  async onPropTaskTypeChanged(): Promise<void> {
    if (this.taskType) this.formData.taskType = this.taskType;
  }

  @Watch('formData.taskType', { immediate: true })
  async onPropFormTaskTypeChanged(): Promise<void> {
    this.hideNotUsesModules = false;
    if (this.formData.taskType) {
      moduleService
        .getUsedModuleNames(this.formData.taskType)
        .then((modules) => {
          const mainModules = this.getIntersection(
            modules,
            this.formData.moduleListMain
          );
          const addOnModules = this.getIntersection(
            modules,
            this.formData.moduleListAddOn
          );
          this.usedModuleNames = [...mainModules, ...addOnModules];
          this.hideNotUsesModules =
            mainModules.length > 0 &&
            modules.length < this.moduleKeyList.length;
          if (!this.taskId) {
            if (mainModules.length > 0) {
              this.mainModule = mainModules[0];
            }
          }
        });
    }
  }

  private getIntersection(
    usedModules: string[],
    modules: { [name: string]: boolean }
  ): string[] {
    const mainModules = Object.keys(modules);
    return mainModules.filter((value) => usedModules.includes(value));
  }

  get mainModule(): string {
    if (this.moduleSelectionMain.length > 0) return this.moduleSelectionMain[0];
    return '';
  }

  set mainModule(value: string) {
    if (value in this.formData.moduleListMain) {
      Object.keys(this.formData.moduleListMain).forEach(
        (moduleName) => (this.formData.moduleListMain[moduleName] = false)
      );
      this.formData.moduleListMain[value] = true;
    }
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

  async updateInputOption(): Promise<void> {
    let input = InputOption.NO;
    for (const moduleIndex in this.moduleKeyList) {
      const moduleName = this.moduleKeyList[moduleIndex];
      if (this.moduleList[moduleName]) {
        await getModuleConfig(
          'input',
          TaskType[this.formData.taskType],
          moduleName
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

  showDialog = false;
  @Watch('showModal', { immediate: false, flush: 'post' })
  async onShowModalChanged(showModal: boolean): Promise<void> {
    this.showDialog = showModal;
  }

  get moduleKeyList(): string[] {
    return [
      ...Object.keys(this.formData.moduleListMain),
      ...Object.keys(this.formData.moduleListAddOn),
    ];
  }

  get displayModuleList(): string[] {
    const moduleNames = this.hideNotUsesModules
      ? this.usedModuleNames
      : this.moduleKeyList;
    if (this.moduleSearch) {
      return moduleNames.filter((moduleName) => {
        const name = (this as any).$t(
          `module.${this.formData.taskType.toLowerCase()}.${moduleName}.description.title`
        );
        return name.toLowerCase().includes(this.moduleSearch.toLowerCase());
      });
    }
    return moduleNames;
  }

  get moduleList(): { [name: string]: boolean } {
    return Object.assign(
      {},
      this.formData.moduleListMain,
      this.formData.moduleListAddOn
    );
  }

  @Watch('moduleList', { immediate: true, deep: true })
  async onModuleListChanged(): Promise<void> {
    await this.updateInputOption();
  }

  get showInput(): boolean {
    return this.inputOption !== InputOption.NO;
  }

  get inputMinCount(): number {
    return this.inputOption === InputOption.YES ? 1 : 0;
  }

  get moduleSelectionMain(): string[] {
    return Object.entries(this.formData.moduleListMain)
      .filter((item) => item[1])
      .map((item) => item[0]);
  }

  get moduleSelectionAddOn(): string[] {
    return Object.entries(this.formData.moduleListAddOn)
      .filter((item) => item[1])
      .map((item) => item[0]);
  }

  get moduleSelection(): string[] {
    return [...this.moduleSelectionMain, ...this.moduleSelectionAddOn];
  }

  removeModuleType(tag: string): void {
    if (tag in this.formData.moduleListAddOn)
      this.formData.moduleListAddOn[tag] = false;
    if (tag in this.formData.moduleListMain)
      this.formData.moduleListMain[tag] = false;
  }

  handleClose(done: { (): void }): void {
    if (!this.taskId) this.reset();
    done();
    this.$emit('update:showModal', false);
    this.hideNotUsesModules = true;
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(id: string): void {
    if (id) {
      taskService.getTaskById(id).then((task) => {
        this.formData.taskType = task.taskType;
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
          if (module.name in this.formData.moduleListAddOn)
            this.formData.moduleListAddOn[module.name] = true;
          else this.mainModule = module.name;
        });
        this.task = task;
      });
    } else {
      this.task = null;
      this.mainModule = 'default';
    }
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

  @Watch('formData.taskType', { immediate: true })
  async onTaskTypeChanged(taskType: string): Promise<void> {
    await this.loadModuleList();
    if (this.$options.components) {
      getAsyncTaskParameter(TaskType[taskType]).then((component) => {
        if (this.$options.components)
          this.$options.components['TaskParameterComponent'] = component;
        this.componentLoadIndex++;
      });
    }
  }

  @Watch('moduleSelection', { immediate: true })
  async onModuleSelectionChanged(moduleSelection: string[]): Promise<void> {
    const oldKeys = this.formData.moduleParameterComponents.map(
      (data) => data.componentName
    );

    const addComponent = async (
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
      await getModuleConfig(
        'icon',
        TaskType[this.formData.taskType],
        moduleName
      ).then((result) => (icon = result));

      hasModule(
        ModuleComponentType.MODERATOR_CONFIG,
        TaskType[this.formData.taskType],
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
      moduleSelection.forEach((moduleName) => {
        const componentName = `ModuleParameterComponents-${this.formData.taskType}-${moduleName}`;
        if (
          this.$options.components &&
          !this.$options.components[componentName]
        ) {
          getAsyncModule(
            ModuleComponentType.MODERATOR_CONFIG,
            TaskType[this.formData.taskType],
            moduleName
          ).then((component) => {
            if (this.$options.components)
              this.$options.components[componentName] = component;
            addComponent(moduleName, componentName);
          });
        } else {
          addComponent(moduleName, componentName);
        }
      });
    }
    //this.componentLoadIndex++;
  }

  participantModuleList: { [name: string]: boolean } = {};
  get hasParticipantModule(): boolean {
    let hasParticipantModule = false;
    this.moduleSelection.forEach((moduleName) => {
      if (this.participantModuleList[moduleName]) hasParticipantModule = true;
    });
    return hasParticipantModule;
  }

  async loadModuleList(): Promise<void> {
    this.formData.moduleListMain = {};
    this.formData.moduleListAddOn = {};
    await getModulesForTaskType(this.formData.taskType, ModuleType.MAIN).then(
      (result) => {
        result.forEach((moduleName, moduleIndex) => {
          if (!this.task)
            this.formData.moduleListMain[moduleName] = moduleIndex == 0;
          else
            this.formData.moduleListMain[moduleName] = this.task.modules.some(
              (model) => model.name == moduleName
            );
        });
      }
    );
    await getModulesForTaskType(this.formData.taskType, ModuleType.ADDON).then(
      (result) => {
        result.forEach((moduleName) => {
          if (!this.task) this.formData.moduleListAddOn[moduleName] = false;
          else
            this.formData.moduleListAddOn[moduleName] = this.task.modules.some(
              (model) => model.name == moduleName
            );
        });
      }
    );

    this.participantModuleList = {};
    if (this.moduleKeyList) {
      this.moduleKeyList.forEach((moduleName) => {
        this.participantModuleList[moduleName] = false;
        hasModule(
          ModuleComponentType.PARTICIPANT,
          TaskType[this.formData.taskType],
          moduleName
        ).then((result) => {
          this.participantModuleList[moduleName] = result;
        });
      });
    }
  }

  reset(): void {
    this.task = null;
    this.formData.taskType = this.taskType;
    this.loadModuleList();
    this.formData.name = '';
    this.formData.description = '';
    this.formData.parameter = {};
    this.formData.input = [];
    this.formData.call = ValidationFormCall.CLEAR_VALIDATE;
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
          taskType: this.formData.taskType,
          name: this.formData.name,
          description: this.formData.description,
          parameter: this.formData.parameter,
          modules: this.moduleSelection,
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
          taskType: this.formData.taskType,
          name: this.formData.name,
          description: this.formData.description,
          parameter: this.formData.parameter,
          state: state,
          order: taskCount,
          modules: this.moduleSelection,
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
    this.$emit('update:showModal', false);
    this.$emit('taskUpdated', task.id);
    if (cleanUp) this.reset();
    this.eventBus.emit(EventType.CHANGE_SETTINGS, {});
  }
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
  background-color: var(--color-purple-light);
  cursor: pointer;
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
