<template>
  <el-form-item
    v-if="!moduleId"
    :label="$t('module.information.personalityTest.moderatorConfig.test')"
    :prop="`${rulePropPath}.test`"
  >
    <el-select v-model="modelValue.test">
      <el-option
        v-for="test of Object.keys(surveyConfig)"
        :key="test"
        :value="test"
        :label="`${test}`"
      >
      </el-option>
    </el-select>
  </el-form-item>
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
