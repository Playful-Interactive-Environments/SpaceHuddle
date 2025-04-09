<template>
  <div>
    <el-form-item
      :label="
        $t('module.information.externalContent.moderatorConfig.participantView')
      "
    >
      <el-switch v-model="modelValue.participantView" />
    </el-form-item>
    <el-form-item
      :label="$t('module.information.externalContent.moderatorConfig.link')"
    >
      <el-input
        type="text"
        v-model="modelValue.sourceLink"
        clearable
        maxlength="4096"
        @blur="cleanEmbedCode"
      />
    </el-form-item>

    <el-form-item
      :label="$t('module.information.externalContent.moderatorConfig.pdf')"
    >
      <label for="file-upload" class="custom-file-upload">
        {{ $t('module.information.externalContent.moderatorConfig.upload') }}
      </label>
      <input
        id="file-upload"
        type="file"
        ref="fileInput"
        @change="onFileChange"
        accept="application/pdf"
      />
    </el-form-item>

    <el-form-item
      :label="$t('module.information.externalContent.moderatorConfig.preview')"
    >
      <div class="iframe-container" v-if="isValidSourceLink">
        <iframe
          :src="getIframeSrc(modelValue.sourceLink)"
          width="100%"
          height="500"
        ></iframe>
      </div>
      <p v-else-if="modelValue.sourceLink">
        {{ $t('module.information.externalContent.moderatorConfig.invalid') }}
      </p>
    </el-form-item>
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

  private currentBlobUrl: string | null = null;

  @Watch('modelValue', { immediate: true })
  async onModelValueChanged(): Promise<void> {
    if (this.modelValue) {
      if (!('sourceLink' in this.modelValue)) {
        this.modelValue.sourceLink = '';
      }
      if (!('participantView' in this.modelValue)) {
        this.modelValue.participantView = false;
      }
    }
  }

  get isValidSourceLink(): boolean {
    const base64Pattern = /^data:application\/pdf;base64,[A-Za-z0-9+/=]+$/;
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
      base64Pattern.test(this.modelValue.sourceLink) ||
      urlPattern.test(this.modelValue.sourceLink) ||
      this.modelValue.sourceLink.includes('<iframe')
    );
  }

  async onFileChange(): Promise<void> {
    const input = this.$refs.fileInput as HTMLInputElement;

    if (input && input.files && input.files.length > 0) {
      const file = input.files[0];

      if (file.type !== 'application/pdf') {
        this.modelValue.sourceLink = '';
        return;
      }

      if (this.currentBlobUrl) {
        URL.revokeObjectURL(this.currentBlobUrl);
      }

      const blobUrl = URL.createObjectURL(file);
      this.currentBlobUrl = blobUrl;

      let base64URL: string | unknown | null = blobUrl;
      await this.convertBlobUrlToBase64(blobUrl)
        .then((base64) => {
          base64URL = base64;
        })
        .catch(() => {
          base64URL = null;
        });
      this.modelValue.sourceLink = base64URL;
    }
  }

  async convertBlobUrlToBase64(blobUrl) {
    const response = await fetch(blobUrl);
    const blob = await response.blob();

    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onloadend = function () {
        resolve(reader.result);
      };
      reader.onerror = function () {
        reject(new Error('Error reading Blob as Base64'));
      };
      reader.readAsDataURL(blob);
    });
  }

  base64ToBlob(base64: string, contentType = 'application/pdf'): Blob {
    const base64Data = base64.includes(',') ? base64.split(',')[1] : base64;

    const byteCharacters = atob(base64Data);
    const byteNumbers = new Array(byteCharacters.length);
    for (let i = 0; i < byteCharacters.length; i++) {
      byteNumbers[i] = byteCharacters.charCodeAt(i);
    }
    const byteArray = new Uint8Array(byteNumbers);
    return new Blob([byteArray], { type: contentType });
  }

  getIframeSrc(sourceLink: string | null): string | null {
    if (!sourceLink) return null;

    const base64Pattern = /^data:application\/pdf;base64,[A-Za-z0-9+/=]+$/;
    if (base64Pattern.test(sourceLink)) {
      try {
        const pdfBlob = this.base64ToBlob(sourceLink);
        // Revoke the old Blob URL if it exists
        if (this.currentBlobUrl) {
          URL.revokeObjectURL(this.currentBlobUrl);
        }
        this.currentBlobUrl = URL.createObjectURL(pdfBlob);
        return this.currentBlobUrl;
      } catch (error) {
        console.error('Error creating Blob URL:', error);
        return null;
      }
    }

    return sourceLink;
  }

  cleanEmbedCode(): void {
    const embedCode = this.modelValue.sourceLink;
    const urlMatch = embedCode.match(/src=["']([^"']+)["']/);
    if (urlMatch && urlMatch[1]) {
      this.modelValue.sourceLink = urlMatch[1];
    } else {
      this.modelValue.sourceLink = embedCode;
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

input[type='file'] {
  display: none;
}
.custom-file-upload {
  border: var(--el-border-color);
  background-color: var(--el-color-white);
  border-radius: var(--border-radius-small);
  display: inline-block;
  padding: 6px 12px;
  cursor: pointer;
}
</style>
