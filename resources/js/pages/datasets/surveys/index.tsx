import { DataTable } from "@/components/ui/data-table";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, router } from "@inertiajs/react";

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'type', label: 'Type' },
    { key: 'title', label: 'Title', truncate: true },
    { key: 'project.name', label: 'Project', truncate: true },
    { key: 'creator.first_name', label: 'Creator', truncate: true },
    { key: 'updater.first_name', label: 'Updater', truncate: true },
    {
        key: 'created_at_api',
        label: 'Created At',
        render: (row: any) => new Date(row.created_at_api).toLocaleDateString(),
    },
];

interface Survey {
    id: number;
    type: string;
    title: string;
    project: {
        name: string;
    };
    creator: {
        first_name: string;
    };
    updater: {
        first_name: string;
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
    data: Survey[];
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

interface SurveysProps {
    surveys: {
        data: LaravelPagination;
        meta: PaginationMeta;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Surveys',
        href: '/surveys',
    },
];

export default function Surveys({ surveys }: SurveysProps) {

    const handlePageChange = (page: number) => {
        router.get('/surveys', { page }, {
            preserveState: true,
            preserveScroll: true,
            only: ['surveys'],
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Surveys" />
            <DataTable
                columns={columns}
                data={{
                    data: surveys?.data?.data ?? [],
                    meta: {
                        current_page: surveys?.meta?.current_page ?? 1,
                        from: surveys?.meta?.from ?? 0,
                        last_page: surveys?.meta?.last_page ?? 1,
                        per_page: surveys?.meta?.per_page ?? 10,
                        to: surveys?.meta?.to ?? 0,
                        total: surveys?.meta?.total ?? 0,
                    },
                }}
            />
        </AppLayout>
    );
}