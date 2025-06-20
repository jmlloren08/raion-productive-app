import { DataTable } from "@/components/ui/data-table";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, router } from "@inertiajs/react";

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'type', label: 'Type' },
    { key: 'title', label: 'Title', truncate: true },
    { key: 'project.name', label: 'Project', truncate: true },
    { key: 'creator.first_name', label: 'Creator' },
    { key: 'assignee.first_name', label: 'Assignee' },
    { key: 'lastActor.first_name', label: 'Last Actor' },
    { key: 'taskList.name', label: 'Task List', truncate: true },
    { key: 'parentTask.title', label: 'Parent Task', truncate: true },
    { key: 'workflowStatus.name', label: 'Workflow Status' },
    { key: 'attachment.name', label: 'Attachment', truncate: true },
    {
        key: 'created_at_api',
        label: 'Created At',
        render: (value: any) => value ? new Date(value).toLocaleString() : 'Not Available',
    },
];

interface Task {
    id: number;
    type: string;
    title: string;
    project: {
        name: string;
    };
    creator: {
        first_name: string;
    };
    assignee: {
        first_name: string;
    };
    lastActor: {
        first_name: string;
    };
    taskList: {
        name: string;
    };
    parentTask: {
        title: string;
    };
    workflowStatus: {
        name: string;
    };
    attachment: {
        name: string;
    };
    created_at_api: string;
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
    data: Task[];
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

interface TasksProps {
    tasks: {
        data: LaravelPagination;
        meta: PaginationMeta;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tasks',
        href: '/tasks',
    }
];

export default function Tasks({ tasks }: TasksProps) {

    const handlePageChange = (page: number) => {
        router.get('/tasks', { page }, {
            preserveState: true,
            preserveScroll: true,
            only: ['tasks'],
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tasks" />
            <DataTable
                columns={columns}
                data={{
                    data: tasks?.data?.data ?? [],
                    meta: {
                        current_page: tasks?.data?.current_page ?? 1,
                        from: tasks?.data?.from ?? 0,
                        last_page: tasks?.data?.last_page ?? 1,
                        per_page: tasks?.data?.per_page ?? 10,
                        to: tasks?.data?.to ?? 0,
                        total: tasks?.data?.total ?? 0,
                    }
                }}
            />
        </AppLayout>
    );
}