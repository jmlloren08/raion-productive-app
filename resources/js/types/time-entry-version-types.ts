// Time Entry Version Types

export interface TimeEntryVersion {
  id: string;
  type: string;
  event: string;
  object_changes: Record<string, any>;
  item_id: string;
  item_type: string;
  created_at_api: string;
  organization_id: string | null;
  creator_id: string | null;
}

export interface TimeEntryVersionStats {
  total_count: number;
  displayed_count: number;
}

export interface TimeEntryVersionsByEvent {
  [event: string]: {
    count: number;
  };
}

export interface TimeEntryVersionsByDate {
  [date: string]: {
    count: number;
    by_event: Record<string, number>;
  };
}

export interface TimeEntryVersionsResponse {
  time_entry_versions: TimeEntryVersion[];
  summary: TimeEntryVersionStats;
  by_event: TimeEntryVersionsByEvent;
  by_date: TimeEntryVersionsByDate;
}

export interface TimeEntryVersionTimeline {
  id: string;
  event: string;
  date: string;
  changes: Record<string, any>;
  creator_id: string | null;
}

export interface TimeEntryHistoryResponse {
  time_entry_id: string;
  version_count: number;
  timeline: TimeEntryVersionTimeline[];
}
