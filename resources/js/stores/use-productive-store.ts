import axios from 'axios';
import { create } from 'zustand';
import { 
    type Company, 
    type Project,
    CompaniesResponseSchema,
    // ProjectsResponseSchema 
} from '../types/productive';

interface ProductiveStore {
    companies: Record<string, Company>;
    projects: Record<string, Project>;
    isLoading: boolean;
    error: string | null;
    fetchData: () => Promise<void>;
}

const API_URL = import.meta.env.VITE_PRODUCTIVE_API_URL as string;
const API_TOKEN = import.meta.env.VITE_PRODUCTIVE_API_TOKEN as string;
const ORGANIZATION_ID = import.meta.env.VITE_PRODUCTIVE_ORGANIZATION_ID as string;

if (!API_URL || !API_TOKEN || !ORGANIZATION_ID) {
    throw new Error('Productive.io API configuration is missing. Please check your .env file.');
}

// Helper function to fetch all pages
async function fetchAllPages(
    url: string,
    headers: any,
    params: any = {}
): Promise<any[]> {
    let allData: any[] = [];
    let nextUrl = url;    let allIncluded: any[] = [];
    while (nextUrl) {
        const response = await axios.get(nextUrl, { headers, params });
        const { data, included, links } = response.data;
        allData = [...allData, ...data];
        if (included) {
            allIncluded = [...allIncluded, ...included];
        }

        // Check if there's a next page
        nextUrl = links?.next || null;
        // Clear params after first request since they're in the URL
        params = {};
    }

    return { data: allData, included: allIncluded };

    // return allData;
}

export const useProductiveStore = create<ProductiveStore>((set, get) => ({
    companies: {},
    projects: {},
    isLoading: false,
    error: null,
    fetchData: async () => {
        set({ isLoading: true, error: null });
        
        try {
            const headers = {
                'X-Auth-Token': API_TOKEN,
                'X-Organization-Id': ORGANIZATION_ID,
                'Content-Type': 'application/vnd.api+json',
                'Accept': 'application/vnd.api+json'
            };

            // First fetch active companies
            // console.log('Fetching companies...');
            const companiesResponse = await axios.get(`${API_URL}/companies`, { 
                headers,
                params: {
                    'page[size]': 100,
                    'filter[status]': 1
                }
            });
            
            const companiesData = CompaniesResponseSchema.parse({ 
                data: companiesResponse.data.data 
            });
            
            // Then fetch active projects with their companies included
            // console.log('Fetching projects...');
            const projectsResponse = await axios.get(`${API_URL}/projects`, {
                headers,
                params: {
                    'page[size]': 100,
                    'filter[project_type]': 2,
                    'filter[status]': 1,
                    'include': 'company'
                }
            });
            
            const { data: projectsData, included } = projectsResponse.data;

            // Initialize companies map
            const companies: Record<string, Company> = {};
            
            // First add companies from direct companies fetch
            companiesData.data.forEach(company => {
                companies[company.id] = {
                    id: company.id,
                    name: company.attributes.name,
                    projects: [],
                    updatedAt: company.attributes.updated_at,
                    createdAt: company.attributes.created_at
                };
            });
            
            // Then add any companies included in the projects response
            if (included) {
                included.forEach((company: any) => {
                    if (company.type === 'companies' && !companies[company.id]) {
                        companies[company.id] = {
                            id: company.id,
                            name: company.attributes.name,
                            projects: [],
                            updatedAt: company.attributes.updated_at,
                            createdAt: company.attributes.created_at
                        };
                    }
                });
            }

            // Process projects and link them to companies
            const projects: Record<string, Project> = {};
            projectsData.forEach((project: any) => {
                try {
                    const companyId = project.relationships?.company?.data?.id;
                    const projectId = project.id;
                    
                    projects[projectId] = {
                        id: projectId,
                        name: project.attributes.name,
                        status: project.attributes.status ?? 1,
                        projectType: project.attributes.project_type ?? 2,
                        companyId: companyId || '',
                        updatedAt: project.attributes.updated_at,
                        createdAt: project.attributes.created_at
                    };
                    
                    if (companyId && companies[companyId]) {
                        companies[companyId].projects.push(projectId);
                    }
                } catch (error) {
                    console.error('Error processing project:', project.id, error);
                }
            });

            // console.log('Final data:', {
            //     companies: Object.keys(companies).length,
            //     projects: Object.keys(projects).length
            // });

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
            
            set({
                error: errorMessage,
                isLoading: false
            });
        }
    }
}));
