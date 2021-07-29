<template>
  <main
    class="
      container container--fullheight-header container--centered
      public-screen__error
    "
  >
    <h2>{{ $t('shared.view.publicScreen.none.header') }}</h2>
    <p>{{ $t('shared.view.publicScreen.none.info') }}</p>
  </main>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';

@Options({
  components: {
  },
})
export default class PublicScreenComponent extends Vue {
  @Prop() readonly taskId!: string;
}
</script>