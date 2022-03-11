export interface CustomParameter {
  updateParameterForSaving(): Promise<void>;
}

export interface CustomSync {
  customSyncPublicParticipant(): boolean;
}
