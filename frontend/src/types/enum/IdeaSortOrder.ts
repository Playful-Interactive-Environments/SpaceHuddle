export const IdeaSortOrderHierarchy = 'hierarchy';
export const IdeaSortOrderView = 'view';

enum IdeaSortOrder {
  TIMESTAMP = 'timestamp',
  ALPHABETICAL = 'alphabetical',
  PARTICIPANT = 'participant',
  COUNT = 'count',
  STATE = 'state',
  INPUT = 'input',
  ORDER = 'order',
}

export const DefaultIdeaSortOrder = IdeaSortOrder.ORDER;

export const DefaultDisplayCount = 20;

export default IdeaSortOrder;
