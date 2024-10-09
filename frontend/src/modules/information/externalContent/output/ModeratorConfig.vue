<template>
  <div>
    <el-form-item
      :label="$t('module.information.externalContent.moderatorConfig.link')"
    >
      <el-input type="text" v-model="modelValue.sourceLink" clearable />
    </el-form-item>

    <el-form-item
      :label="$t('module.information.externalContent.moderatorConfig.pdf')"
    >
      <input
        type="file"
        ref="fileInput"
        @change="onFileChange"
        accept="application/pdf"
      />
    </el-form-item>

    <div class="iframe-container" v-if="isValidSourceLink">
      <iframe :src="modelValue.sourceLink" width="100%" height="500"></iframe>
    </div>
    <p v-else-if="modelValue.sourceLink">
      {{ $t('module.information.externalContent.moderatorConfig.invalid') }}
    </p>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { defaultFormRules, ValidationRuleDefinition } from '@/utils/formRules';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
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

  @Watch('modelValue', { immediate: true })
  async onModelValueChanged(): Promise<void> {
    if (this.modelValue) {
      if (!('sourceLink' in this.modelValue)) {
        this.modelValue.sourceLink = '';
      }
    }
  }

  // Computed property to check if the source link is valid
  get isValidSourceLink(): boolean {
    const urlPattern = new RegExp(
      '^(https?:\\/\\/)' +
        '((([a-z0-9\\-]+\\.)+[a-z]{2,})|' +
        'localhost|' +
        '\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}|' +
        '\\[([0-9a-f]{1,4}:){7}[0-9a-f]{1,4}\\])' +
        '(\\:\\d+)?(\\/[-a-z0-9%_.~+&:]*)*' +
        '(\\?[;&a-z0-9%_.~+=-]*)?' +
        '(\\#[-a-z0-9_]*)?$',
      'i'
    );

    return (
      urlPattern.test(this.modelValue.sourceLink) ||
      this.modelValue.sourceLink.startsWith('blob:')
    );
  }

  onFileChange(event: Event): void {
    const input = this.$refs.fileInput as HTMLInputElement;

    // Ensure the input and files array exists
    if (input && input.files && input.files.length > 0) {
      const file = input.files[0];

      // Check if the uploaded file is a PDF
      if (file.type !== 'application/pdf') {
        console.error('Uploaded file is not a PDF');
        this.modelValue.sourceLink = ''; // Clear the sourceLink if the file type is incorrect
        return;
      }

      // Update sourceLink with the file URL so it can be displayed in the iframe
      this.modelValue.sourceLink = URL.createObjectURL(file);
    } else {
      console.log(input);
      console.error('File input or files are undefined');
    }
  }
}
</script>

<style lang="scss" scoped>
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

.visIcon {
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
  .visIcon {
    color: white;
  }
}
</style>
