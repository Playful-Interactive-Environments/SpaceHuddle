/* eslint-disable @typescript-eslint/no-explicit-any*/
export interface ValidationData {
  [name: string]: any;
}

export interface ValidationRule {
  [name: string]: any;
}

export interface ValidationRules {
  [name: string]: ValidationRule[];
}
