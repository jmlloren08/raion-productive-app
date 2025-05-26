import { z } from 'zod';

// Company Schema
export const CompanySchema = z.object({
    id: z.string(),
    type: z.literal('companies'), attributes: z.object({
        name: z.string(),
        updated_at: z.string().optional().default(new Date().toISOString()),
        created_at: z.string().optional().default(new Date().toISOString())
    }),
    relationships: z.object({
        projects: z.object({
            data: z.array(z.object({
                id: z.string(),
                type: z.literal('projects')
            }))
        }).optional()
    })
});

// Project Schema 
export const ProjectSchema = z.object({
    id: z.string(),
    type: z.literal('projects'), attributes: z.object({
        name: z.string(),
        project_type: z.number().optional().default(2), // 1: internal, 2: client
        status: z.number().optional().default(1), // 1: active, 2: archived
        updated_at: z.string().optional().default(new Date().toISOString()),
        created_at: z.string().optional().default(new Date().toISOString())
    }), relationships: z.object({
        company: z.object({
            data: z.object({
                id: z.string(),
                type: z.literal('companies')
            }).optional().default({ id: '', type: 'companies' })
        }).optional().default({ data: { id: '', type: 'companies' } })
    })
});

// API Response Schemas
export const CompaniesResponseSchema = z.object({
    data: z.array(CompanySchema),
    included: z.array(ProjectSchema).optional()
});

export const ProjectsResponseSchema = z.object({
    data: z.array(ProjectSchema),
    included: z.array(CompanySchema)
});

// Normalized Types
export type Company = {
    id: string;
    name: string;
    projects: string[]; // Array of project IDs
    company_code: string | undefined;
    created_at_api: string | undefined;
};

export type Project = {
    id: string;
    name: string;
    archived_at: boolean; // 1: active, 2: archived
    project_type_id: number; // 1: internal, 2: client
    company_id: string;
    created_at_api: string | undefined;
};

// Deal Schema
export const DealSchema = z.object({
    id: z.string(),
    type: z.literal('deals'),
    attributes: z.object({
        name: z.string(),
        updated_at: z.string().optional().default(new Date().toISOString()),
        created_at: z.string().optional().default(new Date().toISOString())
    }),
    relationships: z.object({
        company: z.object({
            data: z.object({
                id: z.string(),
                type: z.literal('companies')
            }).optional().default({ id: '', type: 'companies' })
        }).optional().default({ data: { id: '', type: 'companies' } }),
        project: z.object({
            data: z.object({
                id: z.string(),
                type: z.literal('projects')
            }).optional().default({ id: '', type: 'projects' })
        }).optional().default({ data: { id: '', type: 'projects' } })
    })
});

export type Deal = {
    id: string;
    name: string;
    companyId: string | null;
    projectId: string | null;
    updatedAt: string | undefined;
    createdAt: string | undefined;
};
