<template>
  <el-card
    ref="ideaCard"
    shadow="never"
    v-on:click="changeSelection"
    :class="{
      card__selected: isSelected,
      card__new: isNew,
      card__handled: isHandled,
      card__thumbs_up: isThumbsUp,
      card__thumbs_down: isThumbsDown,
      card__duplicate: isDuplicate,
      draggable: isDraggable,
      'idea-transform': fadeIn,
    }"
    :body-style="{ padding: '0px' }"
    :style="{ '--card-height': ideaHeight }"
  >
    <img v-if="idea.image" :src="idea.image" class="card__image" alt="" />
    <img
      v-if="idea.link && !idea.image"
      :src="idea.link"
      class="card__image"
      alt=""
    />
    <div class="card__text">
      <div class="card__title">
        <span
          ref="title"
          class="line-break"
          :class="{ threeLineText: cutLongTexts || limitedTextLength }"
        >
          <span v-if="idea.count > 1" class="idea-count">
            {{ idea.count }}x
          </span>
          {{ hasKeywords ? idea.keywords : idea.description }}
        </span>
        <span class="actions">
          <slot name="action"></slot>
          <span class="state" v-if="showState && stateIcon">
            <font-awesome-icon
              v-if="stateIcon"
              :icon="stateIcon"
              :style="{ color: stateColor }"
            />
          </span>
          <el-dropdown
            v-if="isEditable"
            class="card__menu"
            v-on:command="menuItemSelected($event)"
          >
            <span class="el-dropdown-link">
              <font-awesome-icon icon="ellipsis-h" />
            </span>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item command="edit">
                  <font-awesome-icon icon="pen" />
                </el-dropdown-item>
                <el-dropdown-item command="delete">
                  <font-awesome-icon icon="trash" />
                </el-dropdown-item>
                <el-dropdown-item command="state" v-if="canChangeState">
                  <el-dropdown
                    class="card__menu"
                    placement="top-start"
                    v-on:command="menuItemSelected($event)"
                  >
                    <font-awesome-icon icon="star" />
                    <template #dropdown>
                      <el-dropdown-menu>
                        <el-dropdown-item
                          v-for="ideaState in IdeaStates"
                          :key="ideaState"
                          :command="ideaState"
                        >
                          {{ $t(`enum.ideaState.${ideaState}`) }}
                        </el-dropdown-item>
                      </el-dropdown-menu>
                    </template>
                  </el-dropdown>
                </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </span>
      </div>
      <div
        ref="description"
        v-if="hasKeywords && idea.description"
        class="card__content line-break"
        :class="{ threeLineText: cutLongTexts || (limitedTextLength && !ignoreLimitedDescriptionLength) }"
      >
        {{ idea.description }}
      </div>
      <div v-if="isLongText && !cutLongTexts && !ignoreLimitedDescriptionLength" class="collapse">
        <font-awesome-icon
          :icon="limitedTextLength ? 'angle-down' : 'angle-up'"
          @click="collapseChanged"
        />
      </div>
    </div>
    <IdeaSettings
      v-if="isEditable"
      v-model:show-modal="showSettings"
      :idea="idea"
      :authHeaderTyp="authHeaderTyp"
    />
  </el-card>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as ideaService from '@/services/idea-service';
import { Idea } from '@/types/api/Idea';
import IdeaStates from '@/types/enum/IdeaStates';
import IdeaSettings from '@/components/moderator/organisms/settings/IdeaSettings.vue';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';

export enum CollapseIdeas {
  collapseAll = 'collapseAll',
  expandAll = 'expandAll',
  custom = 'custom',
}

@Options({
  components: { IdeaSettings },
  emits: [
    'ideaDeleted',
    'update:isSelected',
    'update:collapseIdeas',
    'update:fadeIn',
  ],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class IdeaCard extends Vue {
  @Prop() idea!: Idea;
  @Prop({ default: true }) isEditable!: boolean;
  @Prop({ default: true }) canChangeState!: boolean;
  @Prop({ default: true }) showState!: boolean;
  @Prop({ default: false }) isSelectable!: boolean;
  @Prop({ default: false, reactive: true }) isSelected!: boolean;
  @Prop({ default: false }) isDraggable!: boolean;
  @Prop({ default: false }) cutLongTexts!: boolean;
  @Prop({ default: false }) fadeIn!: boolean;
  @Prop({ default: false }) ignoreLimitedDescriptionLength!: boolean;
  @Prop({ default: CollapseIdeas.custom }) collapseIdeas!: CollapseIdeas;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
  showSettings = false;
  limitedTextLength = false;
  isLongText = false;
  ideaHeight = '50px';

  IdeaStates = IdeaStates;

  @Watch('fadeIn', { immediate: true })
  onFadeInChanged(): void {
    if (this.fadeIn) {
      setTimeout(() => {
        this.ideaHeight = this.getHeight();
      }, 500);
      setTimeout(() => {
        this.$emit('update:fadeIn', false);
      }, 3000);
    }
  }

  @Watch('collapseIdeas', { immediate: true })
  onCollapseIdeasChanged(): void {
    if (this.collapseIdeas !== CollapseIdeas.custom) {
      this.limitedTextLength =
        this.isLongText && this.collapseIdeas === CollapseIdeas.collapseAll;
    }
  }

  lengthIsCalculating = false;
  @Watch('idea.keywords', { immediate: true })
  @Watch('idea.description', { immediate: true })
  onIdeaChanged(): void {
    if (!this.lengthIsCalculating) {
      this.lengthIsCalculating = true;
      setTimeout(() => {
        let isLongText = false;
        const titleControl: HTMLElement = this.$refs.title as HTMLElement;
        if (titleControl) {
          if (titleControl.clientHeight > 70) isLongText = true;
        }
        const descriptionControl: HTMLElement = this.$refs
          .description as HTMLElement;
        if (descriptionControl) {
          if (descriptionControl.clientHeight > 70) isLongText = true;
        }
        this.isLongText = isLongText;
        this.limitedTextLength =
          this.isLongText && this.collapseIdeas !== CollapseIdeas.expandAll;
        this.lengthIsCalculating = false;
      }, 100);
    }
  }

  getHeight(): string {
    const card = this.$refs.ideaCard as any;
    if (card && card.$el && card.$el.offsetHeight) {
      return `${card.$el.offsetHeight}px`;
    }
    return '50px';
  }

  get stateIcon(): string {
    switch (IdeaStates[this.idea.state]) {
      case IdeaStates.NEW:
        return '';
      case IdeaStates.THUMBS_DOWN:
        return 'thumbs-down';
      case IdeaStates.THUMBS_UP:
        return 'thumbs-up';
      case IdeaStates.DUPLICATE:
        return 'clone';
      case IdeaStates.HANDLED:
        return 'check';
    }
    return '';
  }

  get stateColor(): string {
    switch (IdeaStates[this.idea.state]) {
      case IdeaStates.NEW:
        return '#999999';
      case IdeaStates.THUMBS_DOWN:
        return '#fe6e5d';
      case IdeaStates.THUMBS_UP:
        return '#01cf9e';
      case IdeaStates.DUPLICATE:
        return '#f3a40a';
      case IdeaStates.HANDLED:
        return '#0192d0';
    }
    return '#999999';
  }

  get isNew(): boolean {
    if (this.idea) return IdeaStates[this.idea.state] == IdeaStates.NEW;
    return false;
  }

  get isHandled(): boolean {
    if (this.idea) return IdeaStates[this.idea.state] == IdeaStates.HANDLED;
    return false;
  }

  get isThumbsDown(): boolean {
    if (this.idea) return IdeaStates[this.idea.state] == IdeaStates.THUMBS_DOWN;
    return false;
  }

  get isThumbsUp(): boolean {
    if (this.idea) return IdeaStates[this.idea.state] == IdeaStates.THUMBS_UP;
    return false;
  }

  get isDuplicate(): boolean {
    if (this.idea) return IdeaStates[this.idea.state] == IdeaStates.DUPLICATE;
    return false;
  }

  changeSelection(): void {
    if (this.isSelectable) {
      this.$emit('update:isSelected', !this.isSelected);
    }
  }

  get hasKeywords(): boolean {
    return !!(this.idea.keywords && this.idea.keywords.length > 0);
  }

  async deleteIdea(): Promise<void> {
    ideaService.deleteIdea(this.idea.id, this.authHeaderTyp).then(() => {
      this.$emit('ideaDeleted', this.idea.id);
    });
  }

  menuItemSelected(command: string): void {
    switch (command) {
      case 'edit':
        this.showSettings = true;
        break;
      case 'delete':
        this.deleteIdea();
        break;
      case 'state':
        break;
      case IdeaStates.NEW:
      case IdeaStates.HANDLED:
      case IdeaStates.DUPLICATE:
      case IdeaStates.THUMBS_DOWN:
      case IdeaStates.THUMBS_UP:
        this.idea.state = command;
        ideaService
          .putIdea(this.idea, this.authHeaderTyp)
          .then((idea) => (this.idea.state = idea.state));
        break;
    }
  }

  collapseChanged(): void {
    this.limitedTextLength = !this.limitedTextLength;
    this.$emit('update:collapseIdeas', CollapseIdeas.custom);
  }
}
</script>

<style lang="scss" scoped>
.actions::v-deep > * {
  margin-left: 0.5rem;
}

.draggable {
  cursor: grab;
}

.card {
  &__selected {
    background-color: var(--color-blue);
  }

  &__new {
    border-color: var(--el-card-border-color);
  }

  &__handled {
    border-color: var(--color-blue);
  }

  &__thumbs_down {
    border-color: var(--el-color-error);
  }

  &__thumbs_up {
    border-color: var(--color-mint);
  }

  &__duplicate {
    border-color: var(--el-color-warning);
  }

  &__content {
    color: var(--color-darkblue-light);
  }

  &__text {
    padding: 14px;
  }

  &__title {
    align-items: center;
  }
}

.state {
  background-color: var(--color-background-gray);
  border-radius: 50%;
  aspect-ratio: 1;
  height: 1.5rem;
  display: inline-flex;

  svg {
    margin: auto;
  }
}

.collapse {
  display: flex;
  justify-content: end;
}

.idea-count {
  font-weight: var(--font-weight-bold);
  color: var(--color-mint);
}

.el-card.idea-transform {
  visibility: hidden;
  --margin-delta: 0px;
  animation: fadein 3.5s linear;
  -moz-animation: fadein 3.5s linear; /* Firefox */
  -webkit-animation: fadein 3.5s linear; /* Safari and Chrome */
  -o-animation: fadein 3.5s linear; /* Opera */
  box-sizing: border-box;
  margin: 0 0 calc(var(--margin-delta) + 0.5rem) 0;
  transform-origin: 50% 0;
  //transition: all 0.3s ease-in-out;
}

@keyframes fadein {
  0% {
    transform: scaleY(0);
    visibility: hidden;
    display: none;
  }
  15% {
    visibility: visible;
    display: block;
    transform: scaleY(0);
    background-color: var(--color-primary);
    color: white;
    --margin-delta: calc(-1 * var(--card-height));
  }
  50% {
    transform: scaleY(0.5);
    background-color: var(--color-primary);
    color: white;
    --margin-delta: calc(-1 * var(--card-height) / 2);
  }
  100% {
    transform: scaleY(1);
    background-color: white;
    color: var(--color-primary);
    --margin-delta: 0px;
  }
}
</style>
