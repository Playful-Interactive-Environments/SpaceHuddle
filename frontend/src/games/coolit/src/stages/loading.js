import { Stage, Sprite, game, loader } from 'melonjs';

/**
 * The custom loading screen for 'Cool It'.
 * Loads some assets needed for this stage only - un-load them again once pre-loading is done to save memory!
 *
 * @extends Stage
 * @see Stage
 * @see loader
 */
export default class Loading extends Stage {
  spinnerRed;
  spinnerYellow;
  spinnerPurple;
  spinnerInterval;

  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  onResetEvent(...args) {
    const baseURL = '/assets/games/coolit/';
    game.world.backgroundColor.parseCSS('#F4F4F4');

    // Loads and Adds the planet spinner.
    loader.load(
      {
        name: 'blue',
        type: 'image',
        src: window.origin.toString() + baseURL + 'images/spinnerblue.png',
      },
      () => {
        game.world
          .addChild(
            new Sprite(
              game.renderer.getWidth() * 0.5,
              game.renderer.getHeight() * 0.45,
              {
                image: 'blue',
              }
            ),
            1
          )
          .scale(0.75)
          .rotate(Math.random())
          .setOpacity(0.75);
      }
    );
    loader.load(
      {
        name: 'red',
        type: 'image',
        src: window.origin.toString() + baseURL + 'images/spinnerred.png',
      },
      () => {
        this.spinnerRed = game.world
          .addChild(
            new Sprite(
              game.renderer.getWidth() * 0.5,
              game.renderer.getHeight() * 0.45,
              {
                image: 'red',
              }
            ),
            1
          )
          .rotate(Math.random());
        this.spinnerRed.setOpacity(0.75);
      }
    );
    loader.load(
      {
        name: 'yellow',
        type: 'image',
        src: window.origin.toString() + baseURL + 'images/spinneryellow.png',
      },
      () => {
        this.spinnerYellow = game.world
          .addChild(
            new Sprite(
              game.renderer.getWidth() * 0.5,
              game.renderer.getHeight() * 0.45,
              {
                image: 'yellow',
              }
            ),
            1
          )
          .rotate(Math.random());
        this.spinnerYellow.setOpacity(0.75);
      }
    );
    loader.load(
      {
        name: 'purple',
        type: 'image',
        src: window.origin.toString() + baseURL + 'images/spinnerpurple.png',
      },
      () => {
        this.spinnerPurple = game.world
          .addChild(
            new Sprite(
              game.renderer.getWidth() * 0.5,
              game.renderer.getHeight() * 0.45,
              {
                image: 'purple',
              }
            ),
            1
          )
          .rotate(Math.random());
        this.spinnerPurple.setOpacity(0.75);
      }
    );

    // Loads and Adds the spacehuddle logo.
    loader.load(
      {
        name: 'spacehuddle',
        type: 'image',
        src: window.origin.toString() + baseURL + 'logo.svg',
      },
      () => {
        game.world
          .addChild(
            new Sprite(
              game.renderer.getWidth() * 0.5,
              game.renderer.getHeight() * 0.66,
              {
                image: 'spacehuddle',
              }
            ),
            2
          )
          .scale(2);

        // Sets an interval for the spinner rotations, updated in 60 frames a second.
        this.spinnerInterval = setInterval(() => {
          this.spinnerRed.rotate(0.02);
          this.spinnerYellow.rotate(0.025);
          this.spinnerPurple.rotate(0.03);
        }, 1000 / 60);
      }
    );
  }

  onDestroyEvent(...args) {
    clearInterval(this.spinnerInterval);

    // Since all images loaded in this class will not be needed anymore they should be unloaded to save memory.
    loader.unload({ name: 'spacehuddle', type: 'image' });
    loader.unload({ name: 'blue', type: 'image' });
    loader.unload({ name: 'red', type: 'image' });
    loader.unload({ name: 'yellow', type: 'image' });
    loader.unload({ name: 'purple', type: 'image' });
  }
}
