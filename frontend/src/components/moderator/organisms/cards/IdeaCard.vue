<template>
  <el-card
    :id="idea.id"
    ref="ideaCard"
    shadow="never"
    class="idea-card"
    v-on:click="changeSelection"
    :class="{
      landscape: !portrait,
      card: !portrait,
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
    :style="{
      '--card-height': ideaHeight,
      '--selection-color': selectionColor,
      '--background-color': backgroundColor,
    }"
  >
    <img v-if="idea.image" :src="idea.image" class="card__image" alt="" />
    <img
      v-else-if="idea.link && !idea.image"
      :src="idea.link"
      class="card__image"
      alt=""
    />
    <div v-else class="card__image">
      <slot name="icon"></slot>
    </div>
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
            trigger="click"
            :hide-on-click="!preventClosing"
          >
            <span class="el-dropdown-link" @click="stopPropagation">
              <ToolTip
                :text="$t('moderator.organism.settings.ideaSettings.header')"
              >
                <font-awesome-icon icon="ellipsis-h" />
              </ToolTip>
            </span>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item v-if="isSharable">
                  <ToolTip
                    :placement="'right'"
                    :text="
                      $t(
                        'moderator.organism.processTimeline.activateParticipant'
                      )
                    "
                  >
                    <el-switch
                      v-model="sharedStatus"
                      @click="levelSharedChanged"
                    />
                  </ToolTip>
                </el-dropdown-item>
                <el-dropdown-item command="edit">
                  <ToolTip
                    :placement="'right'"
                    :text="$t('moderator.organism.settings.ideaSettings.edit')"
                  >
                    <font-awesome-icon icon="pen" />
                  </ToolTip>
                </el-dropdown-item>
                <el-dropdown-item command="delete">
                  <ToolTip
                    :placement="'right'"
                    :text="
                      $t('moderator.organism.settings.ideaSettings.delete')
                    "
                  >
                    <font-awesome-icon icon="trash" />
                  </ToolTip>
                </el-dropdown-item>
                <el-dropdown-item
                  command="state"
                  v-if="canChangeState"
                  @mousedown="preventClosing = true"
                >
                  <ToolTip
                    :placement="'right'"
                    :text="$t('moderator.organism.settings.ideaSettings.state')"
                  >
                    <el-dropdown
                      class="card__menu"
                      placement="top-start"
                      v-on:command="menuItemSelected($event)"
                      trigger="click"
                    >
                      <font-awesome-icon icon="star" />
                      <template #dropdown>
                        <el-dropdown-menu @mousedown="preventClosing = false">
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
                  </ToolTip>
                </el-dropdown-item>
                <slot name="dropdown"></slot>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </span>
      </div>
      <slot></slot>
      <div
        ref="description"
        v-if="hasKeywords && idea.description"
        class="card__content line-break"
        :class="{
          threeLineText:
            cutLongTexts ||
            (limitedTextLength && !ignoreLimitedDescriptionLength),
        }"
      >
        {{ idea.description }}
      </div>
      <div
        v-if="isLongText && !cutLongTexts && !ignoreLimitedDescriptionLength"
        class="collapse"
      >
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
      @updateData="updateData"
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
import * as themeColors from '@/utils/themeColors';
import { LevelWorkflowType } from '@/types/game/LevelWorkflowType';
import ToolTip from '@/components/shared/atoms/ToolTip.vue';

export enum CollapseIdeas {
  collapseAll = 'collapseAll',
  expandAll = 'expandAll',
  custom = 'custom',
}

@Options({
  components: { ToolTip, IdeaSettings },
  emits: [
    'ideaDeleted',
    'ideaStartEdit',
    'update:isSelected',
    'update:collapseIdeas',
    'update:fadeIn',
    'sharedStatusChanged',
    'customCommand',
  ],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class IdeaCard extends Vue {
  @Prop() idea!: Idea;
  @Prop({ default: true }) isEditable!: boolean;
  @Prop({ default: true }) handleEditable!: boolean;
  @Prop({ default: true }) canChangeState!: boolean;
  @Prop({ default: true }) isSharable!: boolean;
  @Prop({ default: true }) showState!: boolean;
  @Prop({ default: false }) isSelectable!: boolean;
  @Prop({ default: '#0192d0' }) selectionColor!: string;
  @Prop({ default: '#ffffff' }) backgroundColor!: string;
  @Prop({ default: false, reactive: true }) isSelected!: boolean;
  @Prop({ default: false }) isDraggable!: boolean;
  @Prop({ default: false }) cutLongTexts!: boolean;
  @Prop({ default: false }) fadeIn!: boolean;
  @Prop({ default: false }) ignoreLimitedDescriptionLength!: boolean;
  @Prop({ default: CollapseIdeas.custom }) collapseIdeas!: CollapseIdeas;
  @Prop({ default: true }) portrait!: boolean;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
  showSettings = false;
  limitedTextLength = false;
  isLongText = false;
  ideaHeight = '50px';

  sharedStatus = false;
  preventClosing = false;

  IdeaStates = IdeaStates;

  levelSharedChanged() {
    this.$emit('sharedStatusChanged', this.sharedStatus);
  }

  stopPropagation(event: Event) {
    event.stopPropagation();
  }

  imageLoaded(event: Event): void {
    const id = (event as any).param1;
    if (id === this.idea.id) {
      this.idea.image = (event as any).param2;
    }
  }

  mounted(): void {
    window.addEventListener('imageLoaded', this.imageLoaded, false);
    this.sharedStatus =
      this.idea.parameter.state === LevelWorkflowType.approved;
  }

  unmounted(): void {
    window.removeEventListener('imageLoaded', this.imageLoaded);
  }

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

  updateData(newIdea: Idea): void {
    this.idea.keywords = newIdea.keywords;
    this.idea.description = newIdea.description;
    this.idea.link = newIdea.link;
    this.idea.image = newIdea.image;
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
        return themeColors.getInactiveColor();
      case IdeaStates.THUMBS_DOWN:
        return themeColors.getEvaluatingColor();
      case IdeaStates.THUMBS_UP:
        return themeColors.getBrainstormingColor();
      case IdeaStates.DUPLICATE:
        return themeColors.getInformingColor();
      case IdeaStates.HANDLED:
        return themeColors.getStructuringColor();
    }
    return themeColors.getInactiveColor();
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
        if (this.handleEditable) this.showSettings = true;
        else this.$emit('ideaStartEdit', this.idea.id);
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
          .putIdea(this.idea, this.authHeaderTyp, false)
          .then((idea) => (this.idea.state = idea.state));
        break;
      default:
        this.$emit('customCommand', command);
    }
  }

  collapseChanged(): void {
    this.limitedTextLength = !this.limitedTextLength;
    this.$emit('update:collapseIdeas', CollapseIdeas.custom);
  }
}
</script>

<style lang="scss" scoped>
.actions::v-deep(> *) {
  margin-left: 0.5rem;
}

.draggable {
  cursor: grab;
}

.idea-card {
  --card-color: var(--el-card-border-color);
  border-color: var(--card-color);
  background-color: var(--background-color);
}

.card {
  &__new {
    --card-color: var(--el-card-border-color);
    border-color: var(--card-color);
  }

  &__handled {
    --card-color: var(--color-structuring);
    border-color: var(--card-color);
  }

  &__thumbs_down {
    --card-color: var(--el-color-error);
    border-color: var(--card-color);
  }

  &__thumbs_up {
    --card-color: var(--color-brainstorming);
    border-color: var(--card-color);
  }

  &__duplicate {
    --card-color: var(--el-color-warning);
    border-color: var(--card-color);
  }

  &__selected.el-card::v-deep(.el-card__body) {
    border-style: solid;
    --card-color: var(--selection-color);
    border-color: var(--card-color);
    border-width: 5px;
  }

  &__content {
    color: var(--color-dark-contrast-light);
  }

  &__text {
    padding: 14px;
  }

  &__title {
    align-items: center;
  }
}

.landscape {
  max-width: 100%;
  margin-bottom: 0.5rem;
}

.landscape::v-deep(.el-card__body) {
  display: flex;

  .card__image {
    width: 30%;
    object-fit: contain;
    background-color: var(--card-color);
  }

  .card__text {
    width: 70%;
  }
}

.state {
  background-color: var(--color-background);
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
  color: var(--color-brainstorming);
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
