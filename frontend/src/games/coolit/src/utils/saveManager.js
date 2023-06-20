/**
 * The save-key to use for the entry in the local storage.
 *
 * @type {string}
 */
export const DefaultSaveKey = 'saveData_coolIt';

/**
 * A default template for this game's save structure. Represents the game-state of a player who has never interacted
 * with the game before.
 */
export const DefaultSaveData = {
  totalStars: 0,
  chosenLocale: null,
  levels: {
    city_center: {
      unlocked: false,
      stars: 0,
    },
    ars_electronica: {
      unlocked: true,
      stars: 0,
    },
    railway_station: {
      unlocked: false,
      stars: 0,
    },
  },
  molecules: {
    watervapor: {
      unlocked: false,
      justUnlocked: false,
    },
    carbondioxide: {
      unlocked: false,
      justUnlocked: false,
    },
    methane: {
      unlocked: false,
      justUnlocked: false,
    },
    nitrousoxide: {
      unlocked: false,
      justUnlocked: false,
    },
    ozone: {
      unlocked: false,
      justUnlocked: false,
    },
    fluorinatedgases: {
      unlocked: false,
      justUnlocked: false,
    },
    oxygen: {
      unlocked: true,
      justUnlocked: true,
    },
    nitrogen: {
      unlocked: true,
      justUnlocked: true,
    },
    argon: {
      unlocked: true,
      justUnlocked: true,
    },
    helium: {
      unlocked: true,
      justUnlocked: true,
    },
  },
};

/**
 * Handles all things save-data for the game 'Cool It'. Checks for existing save-data inside the local storage upon
 * initialization and creates a default save-data according to the default template if none was found.
 *
 * IMPORTANT: Needs access to the browser's local storage in order to function without issues.
 */
export default class SaveManager {
  saveKey = DefaultSaveKey;
  saveData;

  constructor() {
    if (!localStorage.getItem(this.saveKey)) {
      this.saveData = JSON.parse(JSON.stringify(DefaultSaveData));
      this.save(this.saveData);
    } else {
      this.saveData = JSON.parse(localStorage.getItem(this.saveKey));
    }
  }

  /**
   * Save the given save-data to the local storage, using the default key specified.
   *
   * @param saveData {Object} The save-data to be written to the local storage.
   * @returns {boolean} Returns true if successful, false otherwise (including the error that occurred, if any at all).
   */
  save(saveData) {
    if (this.saveKey !== null) {
      try {
        localStorage.setItem(this.saveKey, JSON.stringify(saveData));
        return true;
      } catch (e) {
        console.warn('Unable to save data. Reason: ' + e);
        return false;
      }
    } else {
      console.warn('Unable to save data. Reason: saveKey === null');
      return false;
    }
  }

  /**
   * Resets the current save-data to the default template, allowing for a complete re-start of the game with no progress
   * whatsoever.
   */
  resetSave() {
    this.saveData = JSON.parse(JSON.stringify(DefaultSaveData));
    this.save(this.saveData);
  }

  /**
   * @returns {Object} The current save-data object which is held by the corresponding class attribute. Does not
   * access the local storage.
   */
  getSaveData() {
    return this.saveData;
  }
}
