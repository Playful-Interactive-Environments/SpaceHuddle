/* eslint-disable @typescript-eslint/no-explicit-any*/

export interface Module {
  id: string;
  name: string;
  order: number;
  state: string;
  syncPublicParticipant: boolean;
  parameter: any;
}
