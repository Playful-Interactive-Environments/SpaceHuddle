/* eslint-disable @typescript-eslint/no-explicit-any*/

export interface Hierarchy {
  id: string | null;
  parentId: string | null;
  timestamp: string | null;
  description: string | null;
  keywords: string;
  parameter: any | null;
  image: string | null;
  link: string | null;
  order: number | null;
  isOwn: boolean;
}
