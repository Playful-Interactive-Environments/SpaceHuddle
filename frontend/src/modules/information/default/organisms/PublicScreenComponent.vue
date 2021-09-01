<template>
  <section class="container2 container2--centered public-screen__error">
    TODO: implement information public screen!
  </section>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';

@Options({
  components: {},
})
export default class PublicScreenComponent extends Vue {
  @Prop() readonly taskId!: string;
}
</script>
