<template>
  <div class="idea-card">
    <div>
      <div class="idea-card__idea">
        {{ hasKeywords ? idea.keywords : idea.description }}
      </div>
      <div v-if="hasKeywords" class="idea-card__description">
        {{ idea.description }}
      </div>
    </div>
    <div v-if="isDeletable" class="idea-card__delete" @click="deleteIdea"></div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import * as ideaService from '@/services/idea-service';
import { Idea } from '@/types/api/Idea';
import {
  getErrorMessage,
  addError,
  clearErrors,
} from '@/services/exception-service';

@Options({
  components: {},
})
export default class IdeaCard extends Vue {
  @Prop() idea!: Idea;
  @Prop({ default: true }) isDeletable!: boolean;
  errors: string[] = [];

  get hasKeywords(): boolean {
    return !!(this.idea.keywords && this.idea.keywords.length > 0);
  }

  async deleteIdea(): Promise<void> {
    clearErrors(this.errors);
    ideaService.deleteIdea(this.idea.id).then(
      () => {
        this.$emit('ideaDeleted');
      },
      (error) => {
        addError(this.errors, getErrorMessage(error));
      }
    );
  }
}
</script>

<style lang="scss" scoped>
@import '~@/assets/styles/icons.scss';

.idea-card {
  -webkit-column-break-inside: avoid;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background-color: white;
  border-radius: var(--border-radius-small);
  padding: 1.2rem 1rem 1.2rem 1.4rem;
  line-height: 1.2;
  margin-bottom: 1rem;

  &__idea {
    font-weight: var(--font-weight-semibold);
  }

  &__description {
    color: var(--color-darkblue-light);
    margin-top: 0.5rem;
    word-break: break-word;
  }

  &__delete {
    min-width: 18px;
    margin-left: 0.5rem;
    cursor: pointer;
    @include icon-l('~@/assets/icons/trash.svg', var(--color-darkblue));

    &:hover {
      background-color: var(--color-red);
    }
  }
}
</style>
