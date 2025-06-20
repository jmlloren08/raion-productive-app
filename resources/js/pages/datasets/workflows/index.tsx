import { DataTable } from "@/components/ui/data-table";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, router } from "@inertiajs/react";

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'type', label: 'Type' },
    { key: 'name', label: 'Name', truncate: true },
    { key: 'workflowStatus.name', label: 'Status' },
    {
        key: 'archived_at',
        label: 'Archived At',
        render: (value: any) => value ? new Date(value).toLocaleString() : 'Not Available'
    },
];

interface Workflow {
    id: number;
    type: string;
    name: string;
    workflowStatus: {
        name: string
    };
    archived_at: string | null;
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
    data: Workflow[];
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
    meta: PaginationMeta;
}

interface WorkflowsProps {
    workflows: {
        data: LaravelPagination;
        meta: PaginationMeta;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Workflows',
        href: '/workflows',
    }
];

export default function Workflows({ workflows }: WorkflowsProps) {

    const handlePageChange = (page: number) => {
        router.get('/workflows', { page }, {
            preserveState: true,
            preserveScroll: true,
            only: ['workflows'],
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Workflows" />
            <DataTable
                columns={columns}
                data={{
                    data: workflows?.data?.data ?? [],
                    meta: {
                        current_page: workflows?.data?.current_page ?? 1,
                        from: workflows?.data?.from ?? 0,
                        last_page: workflows?.data?.last_page ?? 1,
                        per_page: workflows?.data?.per_page ?? 10,
                        to: workflows?.data?.to ?? 0,
                        total: workflows?.data?.total ?? 0,
                    }
                }}
                onPageChange={handlePageChange}
            />
        </AppLayout>
    );
}