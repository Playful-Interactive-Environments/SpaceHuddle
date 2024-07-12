<template>
  <div>&nbsp;</div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { CustomInit } from '@/types/ui/CustomParameter';
import CustomConfig from '@/modules/information/quiz/organisms/CustomConfig.vue';
import surveyConfig from '@/modules/information/brainhex/data/survey.json';
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

  @Watch('modelValue', { immediate: true })
  async onModelValueChanged(): Promise<void> {
    if (this.modelValue && !this.modelValue.questionType) {
      this.modelValue.questionType = QuestionnaireType.SURVEY;
    }
  }

  async initCreationData(taskId: string): Promise<void> {
    for (let i = 0; i < surveyConfig.questions.length; i++) {
      const config = surveyConfig.questions[i];
      hierarchyService
        .postHierarchy(taskId, {
          keywords: i.toString(),
          description: config.question,
          link: null,
          image: null, // the datebase64 url of created image
          parameter: {
            questionType: config.type,
            playerType: config.playerType,
          },
          order: i,
        })
        .then((question) => {
          if (question.parameter.questionType === QuestionType.ORDER) {
            const index = parseInt(question.keywords);
            const options = surveyConfig.questions[index].options;
            if (options) {
              for (let j = 0; j < options.length; j++) {
                hierarchyService.postHierarchy(taskId, {
                  parentId: question.id,
                  keywords: j.toString(),
                  description: options[j].answer,
                  link: null,
                  image: null, // the datebase64 url of created image
                  parameter: {
                    playerType: options[j].playerType,
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
