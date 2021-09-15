export interface CustomParameter {
  save(taskId: string | null): Promise<void>;
}