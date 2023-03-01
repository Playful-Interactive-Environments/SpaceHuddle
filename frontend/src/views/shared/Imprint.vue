<template>
  <PublicHeader />
  <div class="imprint">
    <h1>{{ $t('shared.view.imprint.imprint') }}</h1>
    <vue-markdown :source="imprint.imprint" />

    <h2>{{ $t('shared.view.imprint.owner') }}</h2>
    <vue-markdown :source="imprint.owner" />

    <h2>{{ $t('shared.view.imprint.rights') }}</h2>
    <vue-markdown :source="imprint.rights" />

    <h2>{{ $t('shared.view.imprint.support') }}</h2>
    <vue-markdown :source="imprint.support" />

    <h2>{{ $t('shared.view.imprint.liability') }}</h2>
    <vue-markdown :source="imprint.liability" /><!---->
  </div>
  <footer>
    <footer>
      <a @click="$router.push(`/`)">
        {{ $t('shared.view.imprint.home') }}
      </a>
    </footer>
  </footer>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import PublicHeader from '@/components/moderator/organisms/layout/PublicHeader.vue';
import VueMarkdown from 'vue-markdown-render';
import { apiExecuteGet } from '@/services/config-service';

@Options({
  components: {
    PublicHeader,
    VueMarkdown,
  },
})
export default class Imprint extends Vue {
  imprint: { [key: string]: string } = {
    imprint: '',
    owner: '',
    rights: '',
    support: '',
    liability: '',
  };
  mounted(): void {
    const url = 'assets/imprint.json';
    apiExecuteGet<{ [key: string]: string }>(url).then((result) => {
      this.imprint = result;
    });
  }
}
</script>

<style lang="scss" scoped>
.logo-header {
  padding: 2rem;
}

.imprint {
  padding: 2rem 2rem;
  display: flex;
  flex: 1;
  box-sizing: border-box;
  flex-direction: column;
  justify-content: center;
  gap: 1rem;
  color: var(--color-darkblue);

  h1 {
    font-size: var(--font-size-xxxlarge);
    font-weight: var(--font-weight-bold);
  }

  h2 {
    font-size: var(--font-size-large);
    font-weight: var(--font-weight-semibold);
  }
}

.imprint::v-deep(a) {
  text-decoration: underline;
}

footer {
  padding: 0 1rem;
}
</style>
