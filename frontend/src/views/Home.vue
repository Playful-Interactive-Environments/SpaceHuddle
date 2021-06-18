<template>
  Moderator
  <nav>
    <router-link to="/sessions">Session Overview</router-link>
  </nav>
  Client
  <nav>
    <router-link to="join">Join session</router-link>
  </nav>
  <div class="container container--fullheight">
    <h1 class="heading heading--big">Heading Big</h1>
    <p>
      Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy
      eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam
      voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet
      clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit
      amet.
    </p>

    <h1 class="heading heading--medium">Heading Medium</h1>
    <p>
      Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy
      eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam
      voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet
      clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit
      amet.
    </p>

    <h1 class="heading heading--regular">Heading Regular</h1>
    <p>
      Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy
      eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam
      voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet
      clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit
      amet.
    </p>

    <h1 class="heading heading--small">Heading Small</h1>
    <p>
      Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy
      eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam
      voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet
      clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit
      amet.
    </p>
    <button class="btn btn--red">Button Red</button>
    <button class="btn btn--yellow">Button Yellow</button>
    <button class="btn btn--mint">Button Mint</button>
    <button class="btn btn--blue">Button Blue</button>
    <button class="btn btn--purple">Button Purple</button>
    <button class="btn btn--wide">Button Wide</button>
    <button class="btn btn--icon btn--wide">
      <img
        src="@/assets/icons/rocket.svg"
        alt="rocket-icon"
        width="20"
        height="auto"
      />
      <span>Button Wide Icon</span>
    </button>
    <button class="btn btn--gradient">Button Gradient</button>
    <button class="btn btn--outline">Button Outline</button>
    <input placeholder="Your text here ..." class="input" />
    <textarea placeholder="Your text here ..." rows="3"></textarea>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import HelloWorld from '@/components/HelloWorld.vue'; // @ is an alias to /src

@Options({
  components: {
    HelloWorld,
  },
})
export default class Home extends Vue {}
</script>
