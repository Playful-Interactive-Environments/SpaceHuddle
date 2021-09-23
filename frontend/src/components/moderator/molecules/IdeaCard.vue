<template>
  <div
    class="idea-card"
    @click="changeSelection"
    :class="{ selected: isSelected }"
  >
    <div class="idea-card-content">
      <div class="idea-card__idea">
        {{ hasKeywords ? idea.keywords : idea.description }}
      </div>
      <div v-if="hasKeywords" class="idea-card__description">
        {{ idea.description }}
      </div>
      <img v-if="idea.image" :src="idea.image" width="200" />
      <img v-if="idea.link" :src="idea.link" width="200" />
    </div>
    <div v-if="isDeletable" class="idea-card__delete" @click="deleteIdea">
      <font-awesome-icon icon="trash" />
    </div>
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
  emits: ['ideaDeleted', 'update:isSelected'],
})
export default class IdeaCard extends Vue {
  @Prop() idea!: Idea;
  @Prop({ default: true }) isDeletable!: boolean;
  @Prop({ default: false }) customDelete!: boolean;
  @Prop({ default: false }) isSelectable!: boolean;
  @Prop({ default: false, reactive: true }) isSelected!: boolean;
  errors: string[] = [];

  changeSelection(): void {
    if (this.isSelectable) {
      this.$emit('update:isSelected', !this.isSelected);
    }
  }

  get hasKeywords(): boolean {
    return !!(this.idea.keywords && this.idea.keywords.length > 0);
  }

  async deleteIdea(): Promise<void> {
    if (this.customDelete) {
      this.$emit('ideaDeleted', this.idea.id);
      return;
    }

    clearErrors(this.errors);
    ideaService.deleteIdea(this.idea.id).then(
      () => {
        this.$emit('ideaDeleted', this.idea.id);
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

.idea-card-content {
  width: 100%;
}

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

    &:hover {
      background-color: var(--color-red);
    }
  }

  &.selected {
    background-color: var(--color-blue);
  }
}
</style>
