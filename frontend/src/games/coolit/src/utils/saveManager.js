// A default save key to be able to look up the save inside the browser's local storage.
export const DefaultSaveKey = 'saveData_coolIt';

// A default template for the game save data with no progress.
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
  upgrades: {
    green_roofs: false,
    improved_filters: false,
    exhaust_usage: false,
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

// A save manager which handles reading and writing to the browser's local storage.
export default class SaveManager {
  saveKey = DefaultSaveKey;
  saveData;

  constructor() {
    if (!localStorage.getItem(this.saveKey)) {
      this.saveData = DefaultSaveData;
      this.save(this.saveData);
    } else {
      this.saveData = JSON.parse(localStorage.getItem(this.saveKey));
    }
  }

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

  resetSave() {
    this.saveData = DefaultSaveData;
    this.save(this.saveData);
  }

  getSaveData() {
    return this.saveData;
  }
}
