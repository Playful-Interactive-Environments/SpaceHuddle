<template>
  <!--<router-view class="pre-formatted" />-->
  <router-view />
</template>

<script lang="ts">
import { Vue, Options } from 'vue-class-component';

@Options({
  components: {},
})
export default class App extends Vue {
  mounted(): void {
    const htmlElement = document.documentElement;
    localStorage.setItem('theme', process.env.VUE_APP_THEME);
    htmlElement.setAttribute('theme', process.env.VUE_APP_THEME);
  }
}
</script>
