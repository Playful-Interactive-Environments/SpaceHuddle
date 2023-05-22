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
import { device, game, loader, pool, state, video, event } from 'melonjs';
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
import {
    Pavement,
    Skyscraper,
    SkyscraperGreenRoof,
    StorefrontBright,
    StorefrontDark,
    BuildingRed,
    BuildingBlue,
    BuildingBeige,
    BuildingApartments,
    CarVan,
    CarSedan,
    CO2Sink
} from '@/games/coolit/src/entities/rectPhys.js';

export default class CoolIt extends Vue {
  // Initialize the game as soon as this component is mounted in the app.
  mounted() {
    device.onReady(() => {
      this.initGame();
      event.on('closeGame', () => {
          loader.unloadAll();
          this.$router.go(-1);
      });
    });
  }

  // Unload everything once this component is removed from the app.
  unmounted() {
    // eslint-disable-next-line @typescript-eslint/no-empty-function
    event.off('closeGame', () => {});
  }

  // Initialize the game and switch to the main menu.
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
    // Calling it 4-5 times ensured the right size on the test device.
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
      pool.register('pavement', Pavement);
      pool.register('skyscraper', Skyscraper);
      pool.register('skyscrapergreenroof', SkyscraperGreenRoof);
      pool.register('storefrontbright', StorefrontBright);
      pool.register('storefrontdark', StorefrontDark);
      pool.register('buildingred', BuildingRed);
      pool.register('buildingblue', BuildingBlue);
      pool.register('buildingbeige', BuildingBeige);
      pool.register('buildingapartments', BuildingApartments);
      pool.register('carvan', CarVan);
      pool.register('carsedan', CarSedan);
      pool.register('co2sink', CO2Sink);

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
