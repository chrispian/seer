// TipTap Transclusion Node — attribute typings (MVP)
export interface TransclusionSpec {
  kind: 'single' | 'list';
  mode: 'ref' | 'copy' | 'live' | 'snapshot';
  uid?: string;
  query?: string;
  context?: { ws?: string; proj?: string };
  layout?: 'block' | 'inline' | 'checklist' | 'table' | 'cards';
  columns?: string[];
  mirrorTodos?: boolean;
  readonly?: boolean;
  createdAt?: number;
  updatedAt?: number;
}

export type IncludeLayout = 'checklist' | 'table' | 'cards';

export interface IncludeCommandArgs {
  uid?: string;
  search?: string;
  query?: string;
  mode?: 'ref' | 'copy';
  layout?: IncludeLayout;
  context?: { ws?: string; proj?: string };
}
