import { DataTable } from "@/components/ui/data-table";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, router } from "@inertiajs/react";

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'type', label: 'Type' },
    { key: 'name', label: 'Name', truncate: true },
    { key: 'color_id', label: 'Color' },
    { key: 'position', label: 'Position' },
    { key: 'category_id', label: 'Category' },
    { key: 'workflow.name', label: 'Workflow' },
];

interface WorkflowStatus {
    id: number;
    type: string;
    name: string;
    color_id: number;
    position: number;
    category_id: number;
    workflow: {
        name: string
    };
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
    data: WorkflowStatus[];
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

interface WorkflowStatusesProps {
    workflowStatuses: {
        data: LaravelPagination;
        meta: PaginationMeta;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Workflow Statuses',
        href: '/workflow-statuses',
    }
];

export default function WorkflowStatuses({ workflowStatuses }: WorkflowStatusesProps) {

    const handlePageChange = (page: number) => {
        router.get('/workflow-statuses', { page }, {
            preserveState: true,
            preserveScroll: true,
            only: ['workflowStatuses'],
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Workflow Statuses" />
            <DataTable
                columns={columns}
                data={{
                    data: workflowStatuses?.data?.data ?? [],
                    meta: {
                        current_page: workflowStatuses?.data?.current_page ?? 1,
                        from: workflowStatuses?.data?.from ?? 0,
                        last_page: workflowStatuses?.data?.last_page ?? 1,
                        per_page: workflowStatuses?.data?.per_page ?? 10,
                        to: workflowStatuses?.data?.to ?? 0,
                        total: workflowStatuses?.data?.total ?? 0,
                    },
                }}
                onPageChange={handlePageChange}
            />
        </AppLayout>
    );
}