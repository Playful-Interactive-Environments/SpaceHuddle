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
    if (!Object.hasOwn) {
      Object.hasOwn = (obj, prop) =>
        Object.prototype.hasOwnProperty.call(obj, prop);
    }
    const htmlElement = document.documentElement;
    localStorage.setItem('theme', process.env.VUE_APP_THEME);
    htmlElement.setAttribute('theme', process.env.VUE_APP_THEME);
    this.$nextTick(() => {
      window.scrollTo(0, 1);
      window.scrollTo(0, 0);
    });
  }
}
</script>
