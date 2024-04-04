<template>
  <div class="visModules">
    <el-card
      class="visModule"
      v-for="visModule in getVisModulesForTaskType()"
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
              visModule.id +
              '.description.title'
          )
        }}
      </h2>
      <div class="awesome-icon">
        <font-awesome-icon :icon="visModule.icon" v-if="visModule.icon" />
      </div>
      <p>
        {{
          $t(
            'module.common.visualisation_master.visModules.' +
              visModule.id +
              '.description.description'
          )
        }}
      </p>
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
  @Prop({ default: {} }) taskType!: any;

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

  getVisModulesForTaskType() {
    const taskType = this.taskType !== 'VOTING' ? 'IDEA' : 'VOTING';
    return Object.values(this.moduleConfig.visModules).filter(
      (module) => module.type === taskType
    );
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
  min-width: 15em;
  margin: 0.625%;

  border-width: 2px;
  border-color: var(--color-primary);
  --el-card-padding: 0.7rem 1rem;
  word-break: break-word;
  background-color: white;
  border-style: dashed;
  text-align: center;
  font-size: var(--font-size-default);
}

.awesome-icon {
  text-align: center;
  width: 100%;
  font-size: 40pt;
}

h2 {
  margin: 0;
}
p {
  margin: 0;
}

.selectedModule {
  background-color: var(--color-dark-contrast-light);
  h2 {
    color: white;
  }
  p {
    color: white;
  }
  .awesome-icon {
    color: white;
  }
}
</style>
