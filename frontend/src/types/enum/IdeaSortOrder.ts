export const IdeaSortOrderCategorisation = 'categorisation';

enum IdeaSortOrder {
  TIMESTAMP = 'timestamp',
  ALPHABETICAL = 'alphabetical',
  STATE = 'state',
  PARTICIPANT = 'participant',
  COUNT = 'count',
  ORDER = 'order',
}

export const DefaultIdeaSortOrder = IdeaSortOrder.ORDER;

export const DefaultDisplayCount = 20;

export default IdeaSortOrder;
