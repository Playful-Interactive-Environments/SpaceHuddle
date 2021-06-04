<template>
  <div class="session-overview container--fullheight">
    <h1>Your Sessions</h1>
    <div class="grid-container">
      <div class="grid-item">
        <div class="blog-card">
          <p class="uppercaseOverview">24.08.2020</p>
          <h3 class="session-title">Marketing Meeting</h3>
          <p>short description about the goals of this session and other stuff...</p>
          <p class="uppercaseOverview">topics</p>
          <p class="uppercaseOverview">modules</p>
          <h4>XAS54K</h4>
          <button class="btn btn--mint">Go to session</button>
        </div>
      </div>
      <div class="grid-item">
        <div class="blog-card">
          <p class="uppercaseOverview">24.08.2020</p>
          <h3 class="session-title">Marketing Meeting</h3>
          <p>short description about the goals of this session and other stuff...</p>
          <p class="uppercaseOverview">topics</p>
          <p class="uppercaseOverview">modules</p>
          <h4>XAS54K</h4>
          <button class="btn btn--mint">Go to session</button>
        </div>
      </div>
      <div class="grid-item">
        <div class="blog-card">
          <p class="uppercaseOverview">24.08.2020</p>
          <h3 class="session-title">Marketing Meeting</h3>
          <p>short description about the goals of this session and other stuff...</p>
          <p class="uppercaseOverview">topics</p>
          <p class="uppercaseOverview">modules</p>
          <h4>XAS54K</h4>
          <button class="btn btn--mint">Go to session</button>
        </div>
      </div>
      <div class="grid-item">
        <div class="blog-card">
          <p class="uppercaseOverview">24.08.2020</p>
          <h3 class="session-title">Marketing Meeting</h3>
          <p>short description about the goals of this session and other stuff...</p>
          <p class="uppercaseOverview">topics</p>
          <p class="uppercaseOverview">modules</p>
          <h4>XAS54K</h4>
          <button class="btn btn--mint">Go to session</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';

export default class SessionOverview extends Vue {}
</script>

<style scoped>

.grid-container {
  display: grid;
  grid-gap: 50px 50px;
  grid-template-columns: auto auto auto;
}

.session-overview {
  background: #E5E5E5;
  white: #fff;
  /* Quick fix für Content Breite */
  padding: 60px 120px 60px 120px;
}
p{
  font-size: 0.8rem;
}
.uppercaseOverview{
  text-transform: uppercase;
  color: #aaaaaa;
}

.blog-card {
  margin: auto;
  padding: 15px 30px 15px 30px;
  display: flex;
  flex-direction: column;
  background: #fff;
  box-shadow: 0.15rem 0.15rem 0.5rem 0.01rem rgba(0, 0, 0, 0.2);
  border-radius: 1rem;
  overflow: hidden;
}
</style>


