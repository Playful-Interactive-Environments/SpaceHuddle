<template>
  <div id="game-content" class="fill_screen" />
</template>

<!--
IMPORTANT NOTE:
Use the attribute lang='js' instead of lang='ts' to get rid of a Typescript error caused by the melon.js loader.
While loader.preload(...) works with a single Object or an Object[], TypeScript throws an error
about mismatching types since the definition apparently only allows single Objects, which completely voids the
advantage of using said pre-loader.
-->
<script lang="js">
import { Vue } from 'vue-class-component';
import { device, game, loader, pool, state, video, event, boot } from 'melonjs';
import Matter from 'matter-js';
import { resources, GameData } from '@/games/coolit/src/resources.js';
import de from '@/games/coolit/locales/de.json';
import en from '@/games/coolit/locales/en.json';
import SaveManager from '@/games/coolit/src/utils/saveManager.js';
import Loading from '@/games/coolit/src/stages/loading.js';
import Menu from '@/games/coolit/src/stages/menu.js';
import Play from '@/games/coolit/src/stages/play.js';
import MenuUI from '@/games/coolit/src/ui/menuUI.js';
import PlayUI from '@/games/coolit/src/ui/playUI.js';
import { MoleculeSink, ColliderRect } from '@/games/coolit/src/entities/matterRect.js';

/**
 * The Vue3-Module for the game 'Cool It'. Add this module wherever needed in your app.
 * This class boots the melonJS Engine upon successful mounting of the module and initializes the game.
 *
 * @extends Vue
 * @see Vue
 */
export default class CoolIt extends Vue {
  // TODO: Manual engine and game boots/re-starts work as described in README.md.
  // TODO: melonJS does not provide this built-in, but they are working on it: see https://github.com/melonjs/melonJS/issues/1091

  mounted() {
    boot();
    device.onReady(() => {
      this.initGame();
      event.on('closeGame', () => {
          loader.unloadAll();
          this.$router.go(-1);
      });
    });
  }

  unmounted() {
    // eslint-disable-next-line @typescript-eslint/no-empty-function
    event.off('closeGame', () => {});
  }

  /**
   * Initializes the game to function properly.
   * Main tasks:
   *    Set basic variables.
   *    Initialize the canvas renderer.
   *    Set up the game stages.
   *    Load the necessary game resources.
   *    Register the necessary objects with the ObjectPool.
   *    Launch the game by changing to a game state.
   */
  initGame() {
    // Initial Locale Setup
    if (navigator.language === 'de-DE') {
      GameData.general.locale = de;
    } else {
      GameData.general.locale = en;
    }

    // Canvas/Renderer Setup (melon-js).
    if (
      !video.init(GameData.general.targetResX, GameData.general.targetResY, {
        parent: 'game-content',
        scale: 'auto',
        scaleMethod: 'fit',
        consoleHeader: false,
      })
    ) {
      alert('Your browser does not support HTML5 canvas.');
      return;
    }

    // Very weird stack of events that actually fixes the scaling issue on Chrome for Android.
    // Calling the event once does nothing. Calling it twice only scales the canvas a little bit.
    // Calling it 4-5 times ensured the right size on the test devices.
    // Does not interfere with other browsers/devices.
    // TODO If possible: Find a better solution than this...
    // TODO Tried but failed: setTimeout() does not solve this issue.
    event.emit(event.WINDOW_ONRESIZE);
    event.emit(event.WINDOW_ONRESIZE);
    event.emit(event.WINDOW_ONRESIZE);
    event.emit(event.WINDOW_ONRESIZE);
    event.emit(event.WINDOW_ONRESIZE);

    // Allow cross-origin loading
    loader.crossOrigin = 'anonymous';

    // Set all user-defined game stages and transitions (including loading screen)
    state.set(state.LOADING, new Loading());
    state.set(state.MENU, new Menu());
    state.set(state.PLAY, new Play());

    // Ignore possible warnings about different types here. .preload(...) works with a single Object as well as an
    // Object[]. If there is an error make sure the script language is set to JavaScript, not Typescript!
    loader.preload(resources, () => {
      // Set up basic game variables and references (SaveManager instance, texture atlases, etc.)
      GameData.physics.engine = Matter.Engine.create();
      GameData.physics.engine.gravity.scale = GameData.physics.gravityScale;
      GameData.instances.saveManager = new SaveManager();
      GameData.instances.menuUI = new MenuUI();
      game.world.addChild(GameData.instances.menuUI, GameData.zOrder.ui);
      GameData.instances.playUI = new PlayUI();
      game.world.addChild(GameData.instances.playUI, GameData.zOrder.ui);

      // Register all objects to be pooled. Specifically: All special objects to be instantiated in levels (e.g. physics objects on the object layer)
      pool.register('rectCollider', ColliderRect);
      pool.register('moleculeSink', MoleculeSink);

      // Change to the Menu
      state.change(state.MENU, false);
    });
  }
}
</script>

<style lang="scss" scoped>
.fill_screen {
  max-height: 100vh;
  display: flex;
  justify-content: center;
}

#game-content canvas {
  margin: 0;
  display: block;
}
</style>
