export const IdeaSortOrderHierarchy = 'hierarchy';
export const IdeaSortOrderView = 'view';

enum IdeaSortOrder {
  TIMESTAMP = 'timestamp',
  ALPHABETICAL = 'alphabetical',
  STATE = 'state',
  PARTICIPANT = 'participant',
  COUNT = 'count',
  ORDER = 'order',
  INPUT = 'input',
}

export const DefaultIdeaSortOrder = IdeaSortOrder.ORDER;

export const DefaultDisplayCount = 20;

export default IdeaSortOrder;
