import { DataTable } from "@/components/ui/data-table";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, router } from "@inertiajs/react";

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'type', label: 'Type' },
    { key: 'name', label: 'Name', truncate: true },
    { key: 'project.name', label: 'Project', truncate: true },
    { key: 'board.name', label: 'Board', truncate: true },
    {
        key: 'archived_at',
        label: 'Archived At',
        render: (value: any) => value ? new Date(value).toLocaleString() : 'Not Archived',
    },
];

interface TaskList {
    id: number;
    type: string;
    name: string;
    project: {
        name: string;
    };
    board: {
        name: string;
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
    data: TaskList[];
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

interface TaskListsProps {
    taskLists: {
        data: LaravelPagination;
        meta: PaginationMeta;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Task Lists',
        href: '/task-lists',
    }
];

export default function TaskLists({ taskLists }: TaskListsProps) {

    const handlePageChange = (page: number) => {
        router.get('/task-lists', { page }, {
            preserveState: true,
            preserveScroll: true,
            only: ['taskLists'],
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Task Lists" />
            <DataTable
                columns={columns}
                data={{
                    data: taskLists?.data?.data ?? [],
                    meta: {
                        current_page: taskLists?.data?.current_page ?? 1,
                        from: taskLists?.data?.from ?? 0,
                        last_page: taskLists?.data?.last_page ?? 1,
                        per_page: taskLists?.data?.per_page ?? 10,
                        to: taskLists?.data?.to ?? 0,
                        total: taskLists?.data?.total ?? 0,
                    }
                }}
                onPageChange={handlePageChange}
            />
        </AppLayout>
    );
}