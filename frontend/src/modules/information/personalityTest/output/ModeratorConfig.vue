<template>
  <div v-if="!moduleId" class="card-section">
    <el-card
      v-for="test in Object.keys(surveyConfig)"
      :key="test"
      @click="modelValue.test = test"
      :class="{
        selectedModule: modelValue.test === test,
      }"
    >
      <h2
        class="heading heading--regular line-break"
        style="overflow: hidden; white-space: nowrap; text-overflow: ellipsis"
      >
        {{
          $t(`module.information.personalityTest.${test}.moderatorConfig.name`)
        }}
      </h2>
      <div class="card-icon">
        <font-awesome-icon
          :icon="[surveyConfig[test].iconPrefix, surveyConfig[test].icon]"
        />
      </div>
      <p>
        {{
          $t(
            `module.information.personalityTest.${test}.moderatorConfig.description`
          )
        }}
      </p>
    </el-card>
  </div>
  <div v-else class="card-section">
    <el-card>
      <h2
        class="heading heading--regular line-break"
        style="overflow: hidden; white-space: nowrap; text-overflow: ellipsis"
      >
        {{
          $t(
            `module.information.personalityTest.${modelValue.test}.moderatorConfig.name`
          )
        }}
      </h2>
      <div class="card-icon">
        <font-awesome-icon
          :icon="[
            surveyConfig[modelValue.test].iconPrefix,
            surveyConfig[modelValue.test].icon,
          ]"
        />
      </div>
      <p>
        {{
          $t(
            `module.information.personalityTest.${modelValue.test}.moderatorConfig.description`
          )
        }}
      </p>
    </el-card>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { CustomInit } from '@/types/ui/CustomParameter';
import CustomConfig from '@/modules/information/quiz/organisms/CustomConfig.vue';
import surveyConfig from '@/modules/information/personalityTest/data/survey.json';
import * as hierarchyService from '@/services/hierarchy-service';
import { QuestionType } from '@/modules/information/quiz/types/Question';
import { QuestionnaireType } from '@/modules/information/quiz/types/QuestionnaireType';

@Options({
  components: { CustomConfig },
  emits: ['update'],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorConfig extends Vue implements CustomInit {
  @Prop() readonly rulePropPath!: string;
  @Prop() readonly moduleId!: string;
  @Prop() readonly taskId!: string;
  @Prop() readonly topicId!: string;
  @Prop({ default: {} }) modelValue!: any;
  @Prop({ default: {} }) formData!: any;

  surveyConfig = surveyConfig;

  @Watch('modelValue', { immediate: true })
  async onModelValueChanged(): Promise<void> {
    if (this.modelValue && !this.modelValue.questionType) {
      this.modelValue.questionType = QuestionnaireType.SURVEY;
    }
    if (this.modelValue && !this.modelValue.test) {
      this.modelValue.test = Object.keys(surveyConfig)[0];
    }
  }

  async initCreationData(taskId: string): Promise<void> {
    for (
      let i = 0;
      i < surveyConfig[this.modelValue.test].questions.length;
      i++
    ) {
      const config = surveyConfig[this.modelValue.test].questions[i];
      hierarchyService
        .postHierarchy(taskId, {
          keywords: i.toString(),
          description: config.question,
          link: null,
          image: null, // the datebase64 url of created image
          parameter: {
            questionType: config.type,
            resultType: config.resultType,
          },
          order: i,
        })
        .then((question) => {
          if (
            question.parameter.questionType === QuestionType.ORDER ||
            question.parameter.questionType === QuestionType.SINGLECHOICE ||
            question.parameter.questionType === QuestionType.MULTIPLECHOICE
          ) {
            const index = parseInt(question.keywords);
            const options =
              surveyConfig[this.modelValue.test].questions[index].options;
            if (options) {
              for (let j = 0; j < options.length; j++) {
                hierarchyService.postHierarchy(taskId, {
                  parentId: question.id,
                  keywords: j.toString(),
                  description: options[j].answer,
                  link: null,
                  image: null, // the datebase64 url of created image
                  parameter: {
                    resultType: options[j].resultType,
                  },
                  order: j,
                });
              }
            }
          }
        });
    }
  }
}
</script>

<style lang="scss" scoped>
.card-section {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
}

.el-card {
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

.card-icon {
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
  .card-icon {
    color: white;
  }
}
</style>
