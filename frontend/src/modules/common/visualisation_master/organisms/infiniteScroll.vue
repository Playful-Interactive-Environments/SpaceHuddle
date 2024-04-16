<template>
  <div id="scrollVis">
    <div
      id="scrollParent"
      :style="`animation: infiniteScroll ${
        fullScrollTime / timeModifier
      }s infinite linear`"
    >
      <div class="scroll-container columnLayout" id="scrollContainer">
        <IdeaCard
          v-for="idea in ideas"
          :key="idea.id"
          class="scroll-item"
          :idea="idea"
          :is-editable="false"
        />
      </div>
      <div class="scroll-container columnLayout" id="scrollContainer2">
        <IdeaCard
          v-for="idea in ideas"
          :key="idea.id"
          class="scroll-item"
          :idea="idea"
          :is-editable="false"
        />
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import * as ideaService from '@/services/idea-service';
import { Prop, Watch } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';
import IdeaSortOrder from '@/types/enum/IdeaSortOrder';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as cashService from '@/services/cash-service';

@Options({
  components: {
    IdeaCard,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class PublicScreen extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: 0 }) readonly timeModifier!: number;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
  ideas: Idea[] = [];

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.deregisterAll();
    ideaService.registerGetIdeasForTask(
      this.taskId,
      IdeaSortOrder.TIMESTAMP,
      null,
      this.updateIdeas,
      this.authHeaderTyp,
      10
    );
  }

  updateIdeas(ideas: Idea[]): void {
    if (ideas.length > 10) {
      while (ideas.length < 70) {
        ideas = ideas.concat(ideas);
      }
      this.ideas = ideas;
    } else {
      this.ideas = ideas;
    }
  }

  fullScrollTime = 40;

  /*scrollModifier = 0;
  scrollInterval = 500;
  infiniteScrolling(): void {
    if (this.scrollModifier === -100) {
      const queue = document.getElementById('scrollParent'); // Get the list whose id is queue.
      if (queue) {
        const elements = queue?.getElementsByClassName('scroll-container'); // Get HTMLCollection of elements with the li tag name.
        if (elements) {
          const element = queue.removeChild(elements[0]); // Remove the child from queue that is the first li element.
          queue.appendChild(element);
          console.log("did it");
        }
      }
    }
    this.scrollModifier -= 1;
    setTimeout(() => {
      this.infiniteScrolling();
    }, this.scrollInterval);
  }
  mounted(): void {
    this.infiniteScrolling();
  }*/

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateIdeas);
  }

  unmounted(): void {
    this.deregisterAll();
  }
}
</script>

<style lang="scss" scoped>
.el-carousel::v-deep(.el-carousel__item) {
  display: flex;
  justify-content: center;
  align-items: center;
}

.public-idea {
  max-width: 20rem;
}

.gallery-item {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100%;
  width: 20rem;
  max-height: 500px;
}

.el-carousel::v-deep(.el-carousel__mask) {
  background-color: unset;
}

.el-card {
  width: 100%;
  height: 100%;
}

.el-card::v-deep(.el-card__body) {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.el-card::v-deep(.card__text) {
  flex-basis: auto;
  flex-grow: 1;
  flex-shrink: 1;
  text-align: inherit;

  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  gap: 0.5rem;
}

.scroll-container {
  overflow: hidden;
  white-space: nowrap;
  width: 100%;
  margin-top: 0.5rem;
}

.scroll-item {
  transition: all 0.5s;
}

.columnLayout {
  width: 100%;
  column-width: 20rem;
  column-gap: 1rem;
  column-fill: balance;
}

#scrollParent {
  width: 100%;
}

#scrollVis {
  width: 100%;
  height: 100%;
  overflow: hidden;
}

@keyframes scroll {
  from {
    transform: translateY(0);
  }
  to {
    transform: translateY(-50%);
  }
}
</style>
<style lang="scss">
@keyframes infiniteScroll {
  from {
    transform: translateY(0);
  }
  to {
    transform: translateY(-50%);
  }
}
</style>
