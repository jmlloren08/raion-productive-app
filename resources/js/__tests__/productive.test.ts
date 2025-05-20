import { describe, expect, it } from 'vitest';
import { CompaniesResponseSchema, ProjectsResponseSchema } from '../types/productive';

describe('Productive API Schema Validation', () => {
    it('validates company data correctly', () => {
        const mockCompanyData = {
            data: [
                {
                    id: '1',
                    type: 'companies',
                    attributes: {
                        name: 'Test Company',
                        created_at: '2024-05-19T12:00:00Z',
                        updated_at: '2024-05-19T12:00:00Z'
                    },
                    relationships: {
                        projects: {
                            data: [
                                {
                                    id: '1',
                                    type: 'projects'
                                }
                            ]
                        }
                    }
                }
            ]
        };

        expect(() => CompaniesResponseSchema.parse(mockCompanyData)).not.toThrow();
    });

    it('validates project data correctly', () => {
        const mockProjectData = {
            data: [
                {
                    id: '1',
                    type: 'projects',
                    attributes: {
                        name: 'Test Project',
                        status: 'active',
                        created_at: '2024-05-19T12:00:00Z',
                        updated_at: '2024-05-19T12:00:00Z'
                    },
                    relationships: {
                        company: {
                            data: {
                                id: '1',
                                type: 'companies'
                            }
                        }
                    }
                }
            ]
        };

        expect(() => ProjectsResponseSchema.parse(mockProjectData)).not.toThrow();
    });

    it('rejects invalid company data', () => {
        const invalidCompanyData = {
            data: [
                {
                    id: '1',
                    type: 'wrong-type',
                    attributes: {
                        wrong_field: 'Test'
                    }
                }
            ]
        };

        expect(() => CompaniesResponseSchema.parse(invalidCompanyData)).toThrow();
    });

    it('rejects invalid project data', () => {
        const invalidProjectData = {
            data: [
                {
                    id: '1',
                    type: 'projects',
                    attributes: {
                        name: 'Test Project',
                        status: 'invalid-status', // Should be active/archived/planned
                        created_at: '2024-05-19T12:00:00Z',
                        updated_at: '2024-05-19T12:00:00Z'
                    }
                }
            ]
        };

        expect(() => ProjectsResponseSchema.parse(invalidProjectData)).toThrow();
    });
});
