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
import { Idea } from '@/services/idea-service';

@Options({
  components: {},
})
export default class IdeaCard extends Vue {
  @Prop({ default: null }) idea!: Idea;
  @Prop({ default: true }) isDeletable!: boolean;

  get hasKeywords(): boolean {
    return !!(this.idea.keywords && this.idea.keywords.length > 0);
  }

  async deleteIdea(): Promise<void> {
    await ideaService.deleteIdea(this.idea.id);
    this.$emit('ideaDeleted');
  }
}
</script>

<style lang="scss" scoped>
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
    height: 24px;
    width: 22px;
    min-width: 18px;
    margin-left: 0.5rem;
    cursor: pointer;
    mask-image: url('../../../assets/icons/trash.svg');
    mask-repeat: no-repeat;
    mask-position: center;
    mask-size: contain;
    background-color: var(--color-darkblue);
    transition: background-color 0.2s;

    &:hover {
      background-color: var(--color-red);
    }
  }
}
</style>
