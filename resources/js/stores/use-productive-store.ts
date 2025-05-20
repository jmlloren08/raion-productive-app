import axios from 'axios';
import { create } from 'zustand';
import { type Company, type Project } from '../types/productive';

interface SyncStatus {
    last_sync: string | null;
    is_syncing: boolean;
    stats: {
        companies_count: number;
        projects_count: number;
        deals_count: number;
    };
}

interface ApiCompany {
    id: string;
    name: string;
    productive_created_at: string;
    productive_updated_at: string;
}

interface ApiProject {
    id: string;
    name: string;
    status: number;
    project_type: number;
    company_id: string;
    productive_created_at: string;
    productive_updated_at: string;
}

interface ProductiveStore {
    companies: Record<string, Company>;
    projects: Record<string, Project>;
    isLoading: boolean;
    error: string | null;
    syncStatus: SyncStatus | null;
    isSyncing: boolean;
    syncError: string | null;
    fetchData: () => Promise<void>;
    checkSyncStatus: () => Promise<void>;
    triggerSync: () => Promise<void>;
}

export const useProductiveStore = create<ProductiveStore>((set, get) => ({
    companies: {},
    projects: {},
    isLoading: false,
    error: null,
    syncStatus: null,
    isSyncing: false,
    syncError: null,

    fetchData: async () => {
        set({ isLoading: true, error: null });
        
        try {
            // Fetch from local API endpoints instead of Productive API
            const [companiesRes, projectsRes] = await Promise.all([
                axios.get<ApiCompany[]>('/companies'),
                axios.get<ApiProject[]>('/projects')
            ]);

            const companies: Record<string, Company> = {};
            const projects: Record<string, Project> = {};

            // Process companies from local database
            companiesRes.data.forEach((company) => {
                companies[company.id] = {
                    id: company.id,
                    name: company.name,
                    projects: [],
                    updatedAt: company.productive_updated_at,
                    createdAt: company.productive_created_at
                };
            });

            // Process projects from local database
            projectsRes.data.forEach((project) => {
                const projectId = project.id;
                projects[projectId] = {
                    id: projectId,
                    name: project.name,
                    status: project.status,
                    projectType: project.project_type,
                    companyId: project.company_id,
                    updatedAt: project.productive_updated_at,
                    createdAt: project.productive_created_at
                };

                // Link project to company
                if (project.company_id && companies[project.company_id]) {
                    companies[project.company_id].projects.push(projectId);
                }
            });

            set({ companies, projects, isLoading: false });
        } catch (err) {
            console.error('Error in fetchData:', err);
            let errorMessage = 'Failed to fetch data';
            
            if (axios.isAxiosError(err) && err.response) {
                const status = err.response.status;
                const data = err.response.data;
                errorMessage = `API Error ${status}: ${data?.errors?.[0]?.title || err.message}`;
                console.error('API Response:', data);
            }
            
            set({ error: errorMessage, isLoading: false });
        }
    },

    checkSyncStatus: async () => {
        try {
            const response = await axios.get<SyncStatus>('/productive/sync/status');
            set({ syncStatus: response.data });
        } catch (err) {
            console.error('Error checking sync status:', err);
            set({ syncError: 'Failed to check sync status' });
        }
    },

    triggerSync: async () => {
        try {
            set({ isSyncing: true, syncError: null });
            const response = await axios.post<SyncStatus>('/productive/sync');
            
            set({ 
                syncStatus: response.data,
                isSyncing: false
            });

            // Refresh local data after sync
            get().fetchData();
        } catch (err) {
            console.error('Error triggering sync:', err);
            set({ 
                syncError: 'Failed to trigger sync',
                isSyncing: false
            });
        }
    }
}));
