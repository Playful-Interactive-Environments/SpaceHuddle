<template>
  <!--<div class="module-container container2--fullheight container2" ref="item">
    <div class="module-container--center">
      {{ $t('enum.moduleType.empty') }}
    </div>
  </div>-->
  <span></span>
</template>

<script>
export default {
  name: "empty"
}
</script>


<style lang="scss" scoped>
.module-container {
  color: #fff;
  background: var(--color-darkblue);
  //background-image: url('~@/assets/illustrations/background.png');
  background-attachment: fixed;
  background-repeat: no-repeat;
  background-size: cover;

  &--center {
    margin: auto;
    vertical-align: middle;
    text-align: center;
  }
}

.container2 {
  padding-bottom: 0;
  max-height: 100vh;
  position: relative;
}
</style>