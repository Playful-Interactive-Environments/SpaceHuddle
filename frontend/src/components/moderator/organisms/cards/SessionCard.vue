<template>
  <el-card shadow="never">
    <div>
      <p class="el-card__date">{{ formatDate(session.creationDate) }}</p>
      <h2 class="heading heading--regular">{{ session.title }}</h2>
      <p class="el-card__description">{{ session.description }}</p>
      <slot name="topics"></slot>
    </div>
    <div class="el-card__content">
      <SessionCode :code="session.connectionKey" :hasBorder="true" />
      <router-link :to="`/session/${session.id}`">
        <button class="btn btn--mint btn--fullwidth">
          {{ $t('moderator.organism.session.overview.select') }}
        </button>
      </router-link>
    </div>
  </el-card>
</template>

<script lang="ts">
import { Prop } from 'vue-property-decorator';
import { Options, Vue } from 'vue-class-component';
import { Session } from '@/types/api/Session';
import { formatDate } from '@/utils/date';
import SessionCode from '@/components/moderator/molecules/SessionCode.vue';

@Options({
  components: {
    SessionCode,
  },
})
export default class SessionCard extends Vue {
  @Prop() session!: Session;

  formatDate = formatDate;
}
</script>

<style lang="scss" scoped>
.el-card {
  padding: 1rem 1.5rem;
  background: #fff;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  height: 100%;

  &__date {
    text-transform: uppercase;
    color: #aaaaaa;
    font-size: var(--font-size-small);
    margin-bottom: 1rem;
  }

  &__description {
    line-clamp: 3;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-align: justify;
  }

  &__content {
    margin-top: 2.5rem;
  }
}
</style>