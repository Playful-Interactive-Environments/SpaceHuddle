<template>
  <section class="container container--centered public-screen__error">
    TODO: implement selection public screen!
  </section>
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