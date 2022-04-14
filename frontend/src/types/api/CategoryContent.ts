import { Idea } from '@/types/api/Idea';
import { Category, CategoryUndefined } from '@/types/api/Category';

export class CategoryContent {
  ideas: Idea[];
  category: Category;

  constructor(category: Category, ideas: Idea[] = []) {
    this.ideas = ideas;
    this.category = category;
  }

  get color(): string | null {
    if (this.category) return this.category.parameter.color;
    return null;
  }

  get id(): string | null {
    if (this.category) return this.category.id;
    return null;
  }

  get order(): number {
    if (this.category && typeof this.category.order !== CategoryUndefined)
      return this.category.order;
    return 100000;
  }
}

export interface CategoryContentList {
  [name: string]: CategoryContent;
}
