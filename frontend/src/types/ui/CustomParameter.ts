export interface CustomParameter {
  updateParameterForSaving(): Promise<void>;
}

export interface CustomInit {
  initCreationData(taskId: string): Promise<void>;
}

export interface CustomSync {
  customSyncPublicParticipant(): boolean;
}
