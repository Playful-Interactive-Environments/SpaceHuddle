<template>
  <el-card
    shadow="never"
    body-style="text-align: center"
    :class="{
      addOn__boarder: isAddOn,
      selected: selected,
    }"
    :style="{
      '--module-color': getColor(),
    }"
    @click="toggleSelection"
  >
    <h2
      class="heading heading--regular line-break"
      style="overflow: hidden; white-space: nowrap; text-overflow: ellipsis"
    >
      {{
        $t(
          `module.${moduleTask.taskType}.${moduleTask.moduleName}.description.title`
        )
      }}
    </h2>
    <div class="awesome-icon">
      <font-awesome-icon :icon="icon" v-if="icon" />
    </div>
    <div v-if="isAddOn" class="addOn__text">
      {{ $t('moderator.organism.cards.moduleCard.addOn') }}
    </div>
    <el-tooltip placement="top">
      <template #content>
        <div style="max-width: calc(var(--app-width) * 0.5)" class="line-break">
          {{
            $t(
              `module.${moduleTask.taskType}.${moduleTask.moduleName}.description.description`
            )
          }}
        </div>
      </template>
      <p
        :class="{ twoLineText: !isAddOn, oneLineText: isAddOn }"
        class="line-break"
      >
        {{
          $t(
            `module.${moduleTask.taskType}.${moduleTask.moduleName}.description.description`
          )
        }}
      </p>
    </el-tooltip>
  </el-card>
</template>

<script lang="ts">
import { Prop, Watch } from 'vue-property-decorator';
import { Options, Vue } from 'vue-class-component';
import { getModuleConfig, ModuleTask } from '@/modules';
import { ModuleType } from '@/types/enum/ModuleType';
import TutorialStep from '@/components/shared/atoms/TutorialStep.vue';
import { getColorOfType } from '@/types/enum/TaskCategory';
import TaskType from '@/types/enum/TaskType';

@Options({
  components: {
    TutorialStep,
  },
  emits: ['update:modelValue', 'update:mainModule'],
})
export default class ModuleCard extends Vue {
  @Prop() moduleTask!: ModuleTask;
  @Prop({ default: false }) modelValue!: boolean;
  @Prop({ default: ModuleTask.createEmpty() }) mainModule!: ModuleTask;
  @Prop({ default: true }) displayTutorial!: boolean;
  icon: string[] | null = null;
  moduleType: string | null = null;

  ModuleType = ModuleType;

  get isAddOn(): boolean {
    return this.moduleType !== ModuleType.MAIN;
  }

  get selected(): boolean {
    return this.modelValue;
  }

  set selected(value: boolean) {
    this.$emit('update:modelValue', value);
  }

  get mainModuleName(): string {
    return this.mainModule.key;
  }

  set mainModuleName(value: string) {
    if (value === this.moduleTask.key) {
      this.$emit('update:mainModule', this.moduleTask);
    }
  }

  toggleSelection(): void {
    if (this.isAddOn) {
      this.selected = !this.selected;
    } else {
      this.mainModuleName = this.moduleTask.key;
    }
  }

  @Watch('moduleTask', { immediate: true })
  onModuleTaskChanged(): void {
    this.loadModuleConfig();
  }

  async loadModuleConfig(): Promise<void> {
    const configs = [
      getModuleConfig(
        'icon',
        this.moduleTask.taskType,
        this.moduleTask.moduleName
      ),
      getModuleConfig(
        'iconPrefix',
        this.moduleTask.taskType,
        this.moduleTask.moduleName
      ),
      getModuleConfig(
        'type',
        this.moduleTask.taskType,
        this.moduleTask.moduleName
      ),
    ];

    Promise.all(configs).then(([icon, iconPrefix, moduleType]) => {
      this.icon = [iconPrefix, icon];
      this.moduleType = moduleType;
    });
  }

  getColor(): string | undefined {
    if (this.moduleTask.taskType) {
      return getColorOfType(this.moduleTask.taskType as TaskType);
    }
  }
}
</script>

<style lang="scss" scoped>
.el-radio::v-deep(.el-radio__label) {
  padding-left: unset;
}

.heading {
  margin: 0;
}

.awesome-icon {
  text-align: center;
  width: 100%;
  font-size: 40pt;
}

.addOn {
  &__text {
    font-weight: var(--font-weight-semibold);
    text-transform: uppercase;
  }

  &__boarder {
    border-style: dashed;
  }
}

.el-card {
  border-width: 2px;
  border-color: var(--color-primary);
  --el-card-padding: 0.7rem 1rem;
  word-break: break-word;
}

.selected {
  background-color: var(--module-color);
  color: white;

  .heading {
    color: white;
  }
}
</style>
