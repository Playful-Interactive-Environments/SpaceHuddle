/* eslint-disable @typescript-eslint/no-explicit-any*/

export default interface ValidationMethods {
  validateField(field: string): void;
  fieldValue(field: string): any;
  clearValidate(): void;
  reset(): void;
  submitData(event: Event | null): Promise<void>;
}
