// Add to your existing types.ts file

export interface TimeEntry {
  id: string;
  type: string;
  date: string;
  time: number;
  billable_time: number;
  note: string | null;
  track_method_id: number | null;
  started_at: string | null;
  timer_started_at: string | null;
  timer_stopped_at: string | null;
  approved: boolean;
  approved_at: string | null;
  invoiced: boolean;
  overhead: boolean;
  rejected: boolean;
  rejected_reason: string | null;
  rejected_at: string | null;
  submitted: boolean;
  task_id: string | null;
  service_id: string | null;
  person_id: string | null;
  deal_id: string | null;
  organization_id: string | null;
}

export interface TimeEntryStats {
  total_count: number;
  total_time: number;
  total_billable_time: number;
  billable_percentage: number;
}

export interface TimeEntriesByDate {
  [date: string]: {
    total_time: number;
    billable_time: number;
    count: number;
  };
}

export interface TimeEntriesResponse {
  time_entries: TimeEntry[];
  summary: TimeEntryStats;
  by_date: TimeEntriesByDate;
}
