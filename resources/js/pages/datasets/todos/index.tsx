import { DataTable } from "@/components/ui/data-table";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, router } from "@inertiajs/react";

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'type', label: 'Type' },
    { key: 'description', label: 'Description', truncate: true },
    { key: 'assignee.first_name', label: 'Assignee' },
    { key: 'deal.name', label: 'Deal', truncate: true },
    { key: 'task.title', label: 'Task', truncate: true },
    {
        key: 'created_at_api',
        label: 'Created At',
        render: (value: any) => value ? new Date(value).toLocaleString() : 'Not Available'
    },
];

interface Todo {
    id: number;
    type: string;
    description: string;
    assignee: {
        first_name: string
    };
    deal: {
        name: string
    };
    task: {
        title: string
    };
    created_at_api: string | null;
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
    data: Todo[];
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

interface TodosProps {
    todos: {
        data: LaravelPagination;
        meta: PaginationMeta;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Todos',
        href: '/todos',
    }
];

export default function Todos({ todos }: TodosProps) {

    const handlePageChange = (page: number) => {
        router.get('/todos', { page }, {
            preserveState: true,
            preserveScroll: true,
            only: ['todos'],
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Todos" />
            <DataTable
                columns={columns}
                data={{
                    data: todos?.data?.data ?? [],
                    meta: {
                        current_page: todos?.data?.current_page ?? 1,
                        from: todos?.data?.from ?? 0,
                        last_page: todos?.data?.last_page ?? 1,
                        per_page: todos?.data?.per_page ?? 10,
                        to: todos?.data?.to ?? 0,
                        total: todos?.data?.total ?? 0,
                    }
                }}
                onPageChange={handlePageChange}
            />
        </AppLayout>
    );
}