<template>
  <div class="blog-card">
    <p class="uppercaseOverview"><slot name="date">24.08.2020</slot></p>
    <h3 class="title"><slot name="title"></slot></h3>
    <p><slot name="description"></slot></p>
    <p class="uppercaseOverview"><slot name="topics"></slot></p>
    <p class="uppercaseOverview"><slot name="modules"></slot></p>
    <slot>My default text</slot>
  </div>
</template>

<script lang="ts">
import { Prop } from 'vue-property-decorator';
import { Vue } from 'vue-class-component';

export default class Card extends Vue {
  @Prop({ default: [] }) errors!: string[];
}
</script>

<style scoped>
p {
  font-size: 0.8rem;
}
.blog-card {
  margin: auto;
  padding: 15px 30px 15px 30px;
  background: #fff;
  box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
  border-radius: var(--border-radius);
  overflow: hidden;
}
.uppercaseOverview {
  text-transform: uppercase;
  color: #aaaaaa;
  font-size: 0.8rem;
}
</style>
