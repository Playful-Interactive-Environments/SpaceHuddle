<template>
  <div class="visModules">
    <el-card
      class="visModule"
      v-for="visModule in moduleConfig.visModules"
      :key="visModule"
      @click="changeSelection(visModule)"
      :class="{
        selectedModule: visModule.id === selectedVisModule,
      }"
    >
      <h2
        class="heading heading--regular line-break"
        style="overflow: hidden; white-space: nowrap; text-overflow: ellipsis"
      >
        {{
          $t(
            'module.common.visualisation_master.visModules.' +
              getVisModuleID(visModule) +
              '.description.title'
          )
        }}
      </h2>
    </el-card>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { ValidationRuleDefinition, defaultFormRules } from '@/utils/formRules';
import moduleConfig from '@/modules/common/visualisation_master/data/moduleConfig.json';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import * as ideaService from '@/services/idea-service';
import IdeaSortOrder from '@/types/enum/IdeaSortOrder';
import * as taskService from '@/services/task-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Task } from '@/types/api/Task';
import ModuleCard from '@/components/moderator/organisms/cards/ModuleCard.vue';
import {getColorOfType} from "@/types/enum/TaskCategory";
import TaskType from "@/types/enum/TaskType";
import {ModuleTask} from "@/modules";

@Options({
  components: { ModuleCard, IdeaCard },
  emits: ['update'],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorConfig extends Vue {
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;
  @Prop() readonly rulePropPath!: string;

  @Prop() readonly moduleId!: string;
  @Prop() readonly taskId!: string;
  @Prop() readonly topicId!: string;
  @Prop({ default: {} }) modelValue!: any;
  @Prop({ default: {} }) formData!: any;

  moduleConfig = moduleConfig;
  selectedVisModule: null | string = null;

  @Watch('modelValue', { immediate: true })
  async onModelValueChanged(): Promise<void> {
    this.selectedVisModule = this.modelValue.visModule;
  }

  changeSelection(visModule) {
    this.modelValue.visModule = visModule;
    this.selectedVisModule = visModule.id;
  }

  mounted() {
    if (this.modelValue.visModule.id) {
      this.selectedVisModule = this.modelValue.visModule.id;
    } else {
      this.selectedVisModule = null;
    }
  }

  getVisModuleID(visModule: string) {
    for (const module in this.moduleConfig.visModules) {
      if (this.moduleConfig.visModules[module] === visModule) {
        return this.moduleConfig.visModules[module].id;
      }
    }
    return null;
  }
}
</script>

<style lang="scss">
.visModules {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
}

.visModule {
  width: 23.75%;
  margin: 0.625%;

  border-width: 2px;
  border-color: var(--color-primary);
  --el-card-padding: 0.7rem 1rem;
  word-break: break-word;
  background-color: white;
  border-style: dashed;
}

h2 {
  text-align: center;
}

.selectedModule {
  background-color: var(--color-dark-contrast-light);
  h2 {
    color: white;
    text-align: center;
  }
}
</style>
