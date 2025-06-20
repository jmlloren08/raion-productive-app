import { DataTable } from '@/components/ui/data-table';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'type', label: 'Type' },
    { key: 'name', label: 'Name', truncate: true },
    { key: 'company.name', label: 'Company', truncate: true },
    { key: 'projectManager.first_name', label: 'Project Manager' },
    { key: 'lastActor.first_name', label: 'Last Actor' },
    { key: 'workflow.name', label: 'Workflow' },
    {
        key: 'last_activity_at',
        label: 'Last Activity At',
        render: (value: any) => value ? new Date(value).toLocaleString() : 'Not Available',
    }
];

interface Project {
    id: number;
    type: string;
    name: string;
    company: {
        name: string;
    };
    projectManager: {
        first_name: string;
    };
    lastActor: {
        first_name: string;
    };
    workflow: {
        name: string;
    };
    last_activity_at: string;
}

interface PaginationMeta {
    current_page: number;
    from: number;
    last_page: number;
    per_page: number;
    to: number;
    total: number;
}

interface LaravelPagination {
    current_page: number;
    data: Project[];
    first_page_url: string;
    from: number;
    last_page: number;
    last_page_url: string;
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number;
    total: number;
}

interface ProjectsProps {
    projects: {
        data: LaravelPagination;
        meta: PaginationMeta;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Projects',
        href: '/projects',
    },
];

export default function Projects({ projects }: ProjectsProps) {

    const handlePageChange = (page: number) => {
        router.get(`/projects`, { page: page }, {
            preserveState: true,
            preserveScroll: true,
            only: ['projects'],
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Projects" />
            <DataTable
                columns={columns}
                data={{
                    data: projects?.data?.data ?? [],
                    meta: {
                        current_page: projects?.data?.current_page ?? 1,
                        from: projects?.data?.from ?? 0,
                        last_page: projects?.data?.last_page ?? 1,
                        per_page: projects?.data?.per_page ?? 10,
                        to: projects?.data?.to ?? 0,
                        total: projects?.data?.total ?? 0
                    }
                }}
                onPageChange={handlePageChange}
            />
        </AppLayout>
    );
}
