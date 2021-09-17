<template>
  <main
    class="
      container2 container2--fullheight-header container2--centered
      public-screen__error
    "
  >
    <h2>{{ $t('module.none.publicScreen.header') }}</h2>
    <p>{{ $t('module.none.publicScreen.info') }}</p>
  </main>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';

@Options({
  components: {},
})
export default class PublicScreen extends Vue {
  @Prop() readonly taskId!: string;
}
</script>
