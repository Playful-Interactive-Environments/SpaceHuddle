// Due to Browser and JS policies all files need to be hosted (i.e. on a static file server).
// For this project: Files must be in the 'frontend/public' folder. It is wise to create a sub-folder structure as well.
// So far, Vue3 only loads necessary assets from 'frontend/public' and no extra asset management is needed.
import Matter from 'matter-js';

/**
 * The base asset url to be used.
 *
 * @type {string}
 */
const base_url = window.location.origin.toString() + '/assets/games/coolit/';

/**
 * All resources necessary for the game to work. This array will be loaded by melonJS' pre-loader.
 * Includes all spritesheets, fonts, levels, etc. which are needed throughout the game.
 * Specific resources (i.e. loading-screen images) may be loaded and un-loaded when actually needed to keep data
 * consumption to a minimum.
 *
 * @type {[{src: string, name: string, type: string}]}
 */
export const resources = [
  {
    name: 'menu',
    type: 'image',
    src: base_url + 'images/menu.png',
  },
  {
    name: 'ui',
    type: 'image',
    src: base_url + 'images/ui.png',
  },
  {
    name: 'spritesheet',
    type: 'image',
    src: base_url + 'images/spritesheet.png',
  },
  {
    name: 'sidescrollerTEST',
    type: 'tmx',
    src: base_url + 'maps/sidescroller.json',
  },
  {
    name: 'spritesheet',
    type: 'tsx',
    src: base_url + 'tilesets/spritesheet.tsx',
  },
  {
    name: 'WorkSans-Regular',
    type: 'image',
    src: base_url + 'fonts/WorkSans-Regular.png',
  },
  {
    name: 'WorkSans-SemiBold',
    type: 'image',
    src: base_url + 'fonts/WorkSans-SemiBold.png',
  },
  {
    name: 'WorkSans-Bold',
    type: 'image',
    src: base_url + 'fonts/WorkSans-Bold.png',
  },
  {
    name: 'WorkSans-Regular',
    type: 'binary',
    src: base_url + 'fonts/WorkSans-Regular.fnt',
  },
  {
    name: 'WorkSans-SemiBold',
    type: 'binary',
    src: base_url + 'fonts/WorkSans-SemiBold.fnt',
  },
  {
    name: 'WorkSans-Bold',
    type: 'binary',
    src: base_url + 'fonts/WorkSans-Bold.fnt',
  },
];

/**
 * All collision groups and categories needed to help categorize collisions in Matter.js.
 * Matter.js uses bitmasks, as such these values need to be powers of 2 (in binary: values with only one bit set).
 * Use bit-wise left-shifts to achieve this.
 * Example: 1 << 0 = 1 (0x00...001), 1 << 1 = 2 (0x00...010), 1 << 2 = 4 (0x00...100)
 *
 * Max. amount of categories/groups with this system: 32 (1 << 31)
 */
export const collisionGroups = Object.freeze({
  CONTROLLABLE: 1 << 0,
  NON_CONTROLLABLE: 1 << 1,
  LIGHT_RAY: 1 << 2,
  HEAT_RAY: 1 << 3,
  ABSORBING: 1 << 4,
  ADSORBING: 1 << 5,
  WORLD: 1 << 6,
  WORLD_BORDER: 1 << 7,
  SINK: 1 << 8,
  UNAVAILABLE: 1 << 9,
});

/**
 * Object which holds all necessary and mostly static game values and constants.
 * Entries include, but are not limited to:
 *    General game setup values (resolution, language, colors, etc.)
 *    Physics-Engine body settings and constants
 *    z-Order presets for the renderer
 *    Difficulty-Presets, object styles, level parameters, ...
 *
 * Also includes references to instances of objects or arrays which need to be accessed by a multitude of classes and locations
 * as well as a spritesheet-mapping to allow for easier sprite-assignment throughout development.
 */
export const GameData = {
  // Locale, Resolution, General Engine Settings, ...
  general: {
    locale: null, // The language set to use. Initialized on start-up. Initial locale is browser language (de or en). User can change the locale in the settings.
    targetResX: 1080,
    targetResY: 1920,
    colors: {
      white: '#f1f1f1',
      good: '#00ff00',
      bad: '#ff0000',
      light: '#ffff00',
    },
  },

  // Everything physics-related.
  physics: {
    // Constant and Containers.
    engine: null,
    gravityScale: 0,
    mouseStiffness: 0.2,
    reactiveSurfaces: [],
    controllableMolecules: [],
    moleculeSinks: [],

    // Physics-Body settings meant for matter.js Bodies.
    bodySettings: {
      // Meant for the user controllable molecules.
      controllable: {
        restitution: 1,
        collisionFilter: {
          category: collisionGroups.CONTROLLABLE,
          group: 0,
          mask:
            collisionGroups.CONTROLLABLE |
            collisionGroups.NON_CONTROLLABLE |
            collisionGroups.UNAVAILABLE |
            collisionGroups.WORLD |
            collisionGroups.WORLD_BORDER |
            collisionGroups.HEAT_RAY,
        },
      },

      // Meant for molecules which need to be returned to the playspace again.
      unavailable: {
        restitution: 1,
        collisionFilter: {
          category: collisionGroups.UNAVAILABLE,
          group: 0,
          mask: collisionGroups.CONTROLLABLE | collisionGroups.NON_CONTROLLABLE,
        },
      },

      // Meant for controllable molecules which can be destroyed at certain spots.
      destructible: {
        restitution: 1,
        collisionFilter: {
          category: collisionGroups.CONTROLLABLE,
          group: 0,
          mask:
            collisionGroups.CONTROLLABLE |
            collisionGroups.NON_CONTROLLABLE |
            collisionGroups.WORLD_BORDER |
            collisionGroups.SINK |
            collisionGroups.HEAT_RAY,
        },
      },

      // Meant for molecules which cannot be controlled.
      noncontrollable: {
        restitution: 1,
        collisionFilter: {
          category: collisionGroups.NON_CONTROLLABLE,
          group: 0,
          mask:
            collisionGroups.NON_CONTROLLABLE |
            collisionGroups.CONTROLLABLE |
            collisionGroups.UNAVAILABLE |
            collisionGroups.WORLD |
            collisionGroups.WORLD_BORDER,
        },
      },

      // Meant for world objects which block (non-)controllables but not light- or heatrays.
      world: {
        isStatic: true,
        collisionFilter: {
          category: collisionGroups.WORLD,
          group: 0,
          mask: collisionGroups.CONTROLLABLE | collisionGroups.NON_CONTROLLABLE,
        },
      },

      // Meant for the abolute world borders at the edges of the canvas.
      worldBorder: {
        isStatic: true,
        collisionFilter: {
          category: collisionGroups.WORLD_BORDER,
          group: 0,
          mask:
            collisionGroups.CONTROLLABLE |
            collisionGroups.NON_CONTROLLABLE |
            collisionGroups.LIGHT_RAY,
        },
      },

      // Meant for the lightrays which fall down to earth after the game started.
      lightray: {
        restitution: 1,
        friction: 0,
        airFriction: 0,
        staticFriction: 0,
        mass: 0,
        velocity: new Matter.Vector.create(0, 1),
        collisionFilter: {
          category: collisionGroups.LIGHT_RAY,
          group: 0,
          mask:
            collisionGroups.WORLD_BORDER |
            collisionGroups.ADSORBING |
            collisionGroups.ABSORBING,
        },
      },

      // Meant for lightrays which have made contact with the floor.
      heatray: {
        restitution: 1,
        friction: 0,
        airFriction: 0,
        staticFriction: 0,
        mass: 0,
        collisionFilter: {
          category: collisionGroups.HEAT_RAY,
          group: 0,
          mask:
            collisionGroups.CONTROLLABLE |
            collisionGroups.ADSORBING |
            collisionGroups.ABSORBING,
        },
      },

      // Meant for physics objects which apply the 'absorbing' effect on lightrays.
      absorbing: {
        isStatic: true,
        collisionFilter: {
          category: collisionGroups.ABSORBING,
          group: 0,
          mask: collisionGroups.LIGHT_RAY | collisionGroups.HEAT_RAY,
        },
      },

      // Meant for physics objects which apply the 'adsorbing' effect on lightrays.
      adsorbing: {
        isStatic: true,
        collisionFilter: {
          category: collisionGroups.ADSORBING,
          group: 0,
          mask: collisionGroups.LIGHT_RAY | collisionGroups.HEAT_RAY,
        },
      },

      // Meant for physics objects which apply the 'absorbing' effect on lightrays and are able to destroy a certain molecule type.
      sinkAbsorbing: {
        isStatic: true,
        collisionFilter: {
          category: collisionGroups.ABSORBING | collisionGroups.SINK,
          group: 0,
          mask: collisionGroups.CONTROLLABLE | collisionGroups.LIGHT_RAY,
        },
      },

      // Meant for physics objects which apply the 'adsorbing' effect on lightrays and are able to destroy a certain molecule type.
      sinkAdsorbing: {
        isStatic: true,
        collisionFilter: {
          category: collisionGroups.ADSORBING | collisionGroups.SINK,
          group: 0,
          mask: collisionGroups.CONTROLLABLE | collisionGroups.LIGHT_RAY,
        },
      },
    },
  },

  // Z-Order references. Increase in-between ranges if needed.
  zOrder: {
    background: 0,
    usables: 25,
    ui: 50,
  },

  // Gameplay relevant references and resources.
  game: {
    // Basic difficulty presets and their settings.
    // Temperature Scale Info: Scales from 15°C to 35°C (1/20 scaleFactor per 1°C)
    difficulty: {
      easy: {
        lightrays: 5,
        minTempScale: 0,
        startTempScale: 0.3,
        twoStarThreshold: 0.5,
        controllables: 1,
        noncontrollables: 10,
      },
      medium: {
        lightrays: 7,
        minTempScale: 0,
        startTempScale: 0.5,
        twoStarThreshold: 0.7,
        controllables: 2,
        noncontrollables: 20,
      },
      hard: {
        lightrays: 9,
        minTempScale: 0,
        startTempScale: 0.7,
        twoStarThreshold: 0.9,
        controllables: 2,
        noncontrollables: 30,
      },
    },

    // Basic molecule parameters for each available molecule.
    molecules: {
      watervapor: {
        maxScale: 0.25,
        tint: '#ffccff',
      },
      nitrousoxide: {
        maxScale: 0.5,
        tint: '#ff6464',
      },
      carbondioxide: {
        maxScale: 0.33,
        tint: '#808080',
      },
      ozone: {
        maxScale: 0.33,
        tint: '#80d0ff',
      },
      methane: {
        maxScale: 0.25,
        tint: '#a4ff64',
      },
      fluorinatedgases: {
        maxScale: 0.5,
        tint: '#ffff64',
      },
      oxygen: {
        maxScale: 0.2,
        tint: '#eeeeee',
      },
      nitrogen: {
        maxScale: 0.2,
        tint: '#eeeeee',
      },
      argon: {
        maxScale: 0.2,
        tint: '#eeeeee',
      },
      helium: {
        maxScale: 0.2,
        tint: '#eeeeee',
      },
    },

    // Basic settings for the different levels.
    levels: {
      ars_electronica: {
        position: {
          factorX: 0.44,
          factorY: 0.29,
        },
        requirement: 0,
        difficulty: 'easy',
        molecules: ['carbondioxide', 'methane', 'watervapor'],
        maxAmountOfMolecules: [3, 2, 3],
      },
      railway_station: {
        position: {
          factorX: 0.523,
          factorY: 0.625,
        },
        requirement: 1,
        difficulty: 'medium',
        molecules: ['carbondioxide', 'ozone', 'watervapor'],
        maxAmountOfMolecules: [4, 3, 1],
      },
      city_center: {
        position: {
          factorX: 0.48,
          factorY: 0.42,
        },
        requirement: 4,
        difficulty: 'hard',
        molecules: ['carbondioxide', 'ozone', 'fluorinatedgases'],
        maxAmountOfMolecules: [4, 3, 4],
      },
    },

    // General image presets
    imagePresets: {
      spritesheetSettings: {
        image: 'spritesheet',
        framewidth: 256,
        frameheight: 256,
      },
      menuSettings: {
        image: 'menu',
        framewidth: 1080,
        frameheight: 1920,
      },
      uiSettings: {
        image: 'ui',
        framewidth: 1080,
        frameheight: 1920,
      },
    },

    // Font names
    fonts: {
      regular: 'WorkSans-Regular',
      semibold: 'WorkSans-SemiBold',
      bold: 'WorkSans-Bold',
    },

    // Mappings for all loaded spritesheets. If a texture-atlas is used, an entry won't be needed.
    imageIds: {
      dialogboxes: {
        small: 0,
        medium: 1,
        large: 2,
      },
      menu: {
        map: 0,
      },
      spritesheet: {
        checkmark: 0,
        cross: 1,
        confirm: 2,
        abort: 3,
        neutral: 4,
        toggleLeft: 5,
        toggleRight: 6,
        pinNone: 7,
        pinOne: 8,
        pinTwo: 9,
        pinThree: 10,
        none: 11,
        one: 12,
        two: 13,
        three: 14,
        singleStar: 15,
        iconDifficulty: 16,
        iconTemperature: 17,
        iconMolecules: 18,
        iconClimateType: 19,
        moleculeInfo: 20,
        settings: 21,
        molecule: 22,
        moleculeHalo: 23,
        important: 24,
        levelSky: 25,
        levelCloud: 26,
        levelCloudFormation: 27,
        pavement: 28,
        skyscraperBlank: 29,
        skyscraperGreen: 30,
        houseBeigeWindowfront: 31,
        houseBlueWindowfront: 32,
        houseOrange: 33,
        houseDarkBlue: 34,
        houseBeige: 35,
        houseRed: 36,
        carVan: 37,
        carSedan: 38,
        park: 39,
        debug: 62,
        empty: 63,
      },
    },
  },

  // Important references to instances of variable functionality. UI-Containers, Save-Managers, Level-Managers, ...
  instances: {
    saveManager: null, // Handles saving and loading to the localStorage. Initialized on start-up.
    menuUI: null, // MenuUI instance which is persistent across all stages/scenes. Initialized on start-up.
    playUI: null, // PlayUI instance which is persistent across all stages/scenes. Initialized on start-up.
  },
};
