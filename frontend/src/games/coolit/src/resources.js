// Due to Browser and JS policies all files need to be hosted (i.e. on a static file server).
// For this project: Files must be in the 'frontend/public' folder. It is wise to create a sub-folder structure as well.
// So far, Vue3 only loads necessary assets from 'frontend/public' and no extra asset management is needed.
const base_url = window.location.origin.toString() + '/assets/games/coolit/';

// A list of resources which will be loaded by the pre-loader.
export const resources = [
  {
    name: 'menu',
    type: 'image',
    src: base_url + 'images/menu.png',
  },
  {
    name: 'dialogboxes',
    type: 'image',
    src: base_url + 'images/dialogboxes.png',
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

// Game specific variables and references which need to be accessed from everywhere.
export const GameData = {
  // Locale, Resolution, General Engine Settings, ...
  general: {
    locale: null, // The language set to use. Initialized on start-up. Initial locale is browser language (de or en). User can change the locale in the settings.
    targetResX: 1080,
    targetResY: 1920,
  },
  // Everything physics-related, be it matter.js or melonjs.
  physics: {
    engine: null,
    gravityScale: 0,
    mouseStiffness: 0.2,
    reactiveSurfaces: [],
    controllableMolecules: [],
    moleculeSinks: [],
  },
  // Z-Order references. Increase in-between ranges if needed.
  zOrder: {
    background: 0,
    usables: 25,
    ui: 50,
  },
  // Gameplay relevant references and resources.
  game: {
    difficulty: {
      easy: {
        lightrays: 5,
        minTempScale: 0.1,
        startTempScale: 0.3,
        twoStarThreshold: 0.5,
        controllables: 1,
        noncontrollables: 10,
      },
      medium: {
        lightrays: 7,
        minTempScale: 0.3,
        startTempScale: 0.5,
        twoStarThreshold: 0.7,
        controllables: 2,
        noncontrollables: 20,
      },
      hard: {
        lightrays: 9,
        minTempScale: 0.5,
        startTempScale: 0.7,
        twoStarThreshold: 0.9,
        controllables: 2,
        noncontrollables: 30,
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
        molecules: ['carbondioxide', 'nitrousoxide', 'watervapor'],
      },
      railway_station: {
        position: {
          factorX: 0.523,
          factorY: 0.625,
        },
        requirement: 1,
        difficulty: 'medium',
        molecules: ['carbondioxide', 'ozone', 'watervapor'],
      },
      city_center: {
        position: {
          factorX: 0.48,
          factorY: 0.42,
        },
        requirement: 4,
        difficulty: 'hard',
        molecules: ['carbondioxide', 'ozone', 'fluorinatedgases'],
      },
    },
    // Mappings for all loaded spritesheets. If a texture atlas is used as an image, an entry won't be needed.
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
        moleculeinfo: 5,
        settings: 6,
        pinnone: 7,
        pinone: 8,
        pintwo: 9,
        pinthree: 10,
        none: 11,
        one: 12,
        two: 13,
        three: 14,
        icondifficulty: 15,
        icontemperature: 16,
        iconmolecules: 17,
        iconclimatetype: 18,
        veryeasy: 19,
        easy: 20,
        medium: 21,
        hard: 22,
        veryhard: 23,
        thermofront: 24,
        thermomid: 25,
        thermoback: 26,
        watervapor: 27,
        nitrousoxide: 28,
        carbondioxide: 29,
        ozone: 30,
        methane: 31,
        fluorinatedgases: 32,
        other: 33,
        sky: 34,
        cloud: 35,
        cloudformation: 36,
        pavement: 37,
        skyscraperblank: 38,
        skyscrapergreenroof: 39,
        housewindowfront: 40,
        housestorefront: 41,
        housered: 42,
        housedark: 43,
        housebeige: 44,
        housebalconies: 45,
        carvan: 46,
        carsedan: 47,
        heatwave: 48,
        moleculeInfoExcl: 49,
        watervaporExcl: 50,
        nitrousoxideExcl: 51,
        carbondioxideExcl: 52,
        ozoneExcl: 53,
        methaneExcl: 54,
        fluorinatedgasesExcl: 55,
        otherExcl: 56,
        toggleleft: 57,
        toggleright: 58,
        singlestar: 59,
        co2sink: 60,
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

// Collision groups and categories for matter.js.
// Since matter.js uses bitmasks, these values need to be powers of 2 (in binary: values with only one bit set).
// Use bitwise left-shifts to achieve this.
// Examples: 1 << 0 = 1 (0x00...001), 1 << 1 = 2 (0x00...010), 1 << 2 = 4 (0x00...100)
// Max. amount of groups with this system: 32 (up to 1 << 31)
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
});
