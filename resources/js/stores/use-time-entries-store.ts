import { create } from 'zustand';
import axios from 'axios';
import { TimeEntry, TimeEntryStats, TimeEntriesByDate } from '@/types';

interface TimeEntriesState {
  // Data
  timeEntries: TimeEntry[];
  timeEntryStats: TimeEntryStats | null;
  entriesByDate: TimeEntriesByDate;
  
  // State
  isLoading: boolean;
  error: string | null;
  
  // Filters
  dateFrom: string | null;
  dateTo: string | null;
  personId: string | null;
  taskId: string | null;
  serviceId: string | null;
  dealId: string | null;
  
  // Actions
  fetchTimeEntries: () => Promise<void>;
  setDateFilter: (from: string | null, to: string | null) => void;
  setPersonFilter: (personId: string | null) => void;
  setTaskFilter: (taskId: string | null) => void;
  setServiceFilter: (serviceId: string | null) => void;
  setDealFilter: (dealId: string | null) => void;
  clearFilters: () => void;
}

export const useTimeEntriesStore = create<TimeEntriesState>((set, get) => ({
  // Initial data
  timeEntries: [],
  timeEntryStats: null,
  entriesByDate: {},
  
  // Initial state
  isLoading: false,
  error: null,
  
  // Initial filters
  dateFrom: null,
  dateTo: null,
  personId: null,
  taskId: null,
  serviceId: null,
  dealId: null,
  
  // Actions
  fetchTimeEntries: async () => {
    set({ isLoading: true, error: null });
    
    try {
      // Construct URL with filters
      const { dateFrom, dateTo, personId, taskId, serviceId, dealId } = get();
      let url = '/time-entries?limit=100';
      
      if (dateFrom) url += `&date_from=${dateFrom}`;
      if (dateTo) url += `&date_to=${dateTo}`;
      if (personId) url += `&person_id=${personId}`;
      if (taskId) url += `&task_id=${taskId}`;
      if (serviceId) url += `&service_id=${serviceId}`;
      if (dealId) url += `&deal_id=${dealId}`;
      
      const response = await axios.get(url);
      
      set({
        timeEntries: response.data.time_entries,
        timeEntryStats: response.data.summary,
        entriesByDate: response.data.by_date,
        isLoading: false
      });
    } catch (error) {
      console.error('Error fetching time entries:', error);
      set({
        error: 'Failed to fetch time entries',
        isLoading: false
      });
    }
  },
  
  setDateFilter: (from, to) => set({ dateFrom: from, dateTo: to }),
  setPersonFilter: (personId) => set({ personId }),
  setTaskFilter: (taskId) => set({ taskId }),
  setServiceFilter: (serviceId) => set({ serviceId }),
  setDealFilter: (dealId) => set({ dealId }),
  
  clearFilters: () => set({
    dateFrom: null,
    dateTo: null,
    personId: null,
    taskId: null,
    serviceId: null,
    dealId: null,
  })
}));
