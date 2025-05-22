import { create } from 'zustand';
import axios from 'axios';
import { 
  TimeEntryVersion, 
  TimeEntryVersionStats, 
  TimeEntryVersionsByEvent,
  TimeEntryVersionsByDate,
  TimeEntryVersionTimeline
} from '@/types';

interface TimeEntryVersionsState {
  // Data
  timeEntryVersions: TimeEntryVersion[];
  versionStats: TimeEntryVersionStats | null;
  versionsByEvent: TimeEntryVersionsByEvent;
  versionsByDate: TimeEntryVersionsByDate;
  timeEntryHistory: TimeEntryVersionTimeline[];
  
  // State
  isLoading: boolean;
  isLoadingHistory: boolean;
  error: string | null;
  
  // Filters
  timeEntryId: string | null;
  event: string | null;
  dateFrom: string | null;
  dateTo: string | null;
  
  // Actions
  fetchTimeEntryVersions: () => Promise<void>;
  fetchTimeEntryHistory: (timeEntryId: string) => Promise<void>;
  setTimeEntryFilter: (timeEntryId: string | null) => void;
  setEventFilter: (event: string | null) => void;
  setDateFilter: (from: string | null, to: string | null) => void;
  clearFilters: () => void;
}

export const useTimeEntryVersionsStore = create<TimeEntryVersionsState>((set, get) => ({
  // Initial data
  timeEntryVersions: [],
  versionStats: null,
  versionsByEvent: {},
  versionsByDate: {},
  timeEntryHistory: [],
  
  // Initial state
  isLoading: false,
  isLoadingHistory: false,
  error: null,
  
  // Initial filters
  timeEntryId: null,
  event: null,
  dateFrom: null,
  dateTo: null,
  
  // Actions
  fetchTimeEntryVersions: async () => {
    set({ isLoading: true, error: null });
    
    try {
      // Construct URL with filters
      const { timeEntryId, event, dateFrom, dateTo } = get();
      let url = '/time-entry-versions?limit=100';
      
      if (timeEntryId) url += `&time_entry_id=${timeEntryId}`;
      if (event) url += `&event=${event}`;
      if (dateFrom) url += `&date_from=${dateFrom}`;
      if (dateTo) url += `&date_to=${dateTo}`;
      
      const response = await axios.get(url);
      
      set({
        timeEntryVersions: response.data.time_entry_versions,
        versionStats: response.data.summary,
        versionsByEvent: response.data.by_event,
        versionsByDate: response.data.by_date,
        isLoading: false
      });
    } catch (error) {
      console.error('Error fetching time entry versions:', error);
      set({
        error: 'Failed to fetch time entry versions',
        isLoading: false
      });
    }
  },
  
  fetchTimeEntryHistory: async (timeEntryId) => {
    set({ isLoadingHistory: true, error: null });
    
    try {
      const response = await axios.get(`/time-entries/${timeEntryId}/history`);
      
      set({
        timeEntryHistory: response.data.timeline,
        isLoadingHistory: false
      });
    } catch (error) {
      console.error('Error fetching time entry history:', error);
      set({
        error: 'Failed to fetch time entry history',
        isLoadingHistory: false
      });
    }
  },
  
  setTimeEntryFilter: (timeEntryId) => set({ timeEntryId }),
  setEventFilter: (event) => set({ event }),
  setDateFilter: (from, to) => set({ dateFrom: from, dateTo: to }),
  
  clearFilters: () => set({
    timeEntryId: null,
    event: null,
    dateFrom: null,
    dateTo: null,
  })
}));
