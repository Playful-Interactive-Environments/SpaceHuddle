import { Idea } from '@/types/api/Idea';
import { Hierarchy } from '@/types/api/Hierarchy';

/* eslint-disable @typescript-eslint/no-explicit-any*/
export interface Vote {
  id: string;
  ideaId: string;
  rating: number;
  detailRating: number;
  parameter: any;
  timestamp: string;
}

export interface VoteResult {
  idea: Idea | Hierarchy;
  ratingSum: number;
  detailRatingSum: number;
  countParticipant: number;
}

export interface VoteResultDetail extends VoteResult {
  rating: number;
  detailRating: number;
}

export interface VoteParameterResult {
  ideaId: string;
  sum: number;
  avg: number;
  count: number;
}
