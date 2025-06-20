import { DataTable } from "@/components/ui/data-table";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, router } from "@inertiajs/react";

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'type', label: 'Type' },
    { key: 'name', label: 'Name', truncate: true },
    { key: 'creator.first_name', label: 'Creator' },
    { key: 'updater.first_name', label: 'Updater' },
    {
        key: 'created_at',
        label: 'Created At',
        render: (value: any) => value ? new Date(value).toLocaleString() : 'Not Available',
    },
];

interface Pipeline {
    id: number;
    type: string;
    name: string;
    creator: {
        first_name: string;
    };
    updater: {
        first_name: string;
    };
    created_at: string;
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
    data: Pipeline[];
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

interface PipelinesProps {
    pipelines: {
        data: LaravelPagination;
        meta: PaginationMeta;
    }
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pipelines',
        href: '/pipelines',
    }
];

export default function Pipelines({ pipelines }: PipelinesProps) {

    const handlePageChange = (page: number) => {
        router.get('/pipelines', { page }, {
            preserveState: true,
            preserveScroll: true,
            only: ['pipelines'],
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pipelines" />
            <DataTable
                columns={columns}
                data={{
                    data: pipelines?.data?.data ?? [],
                    meta: {
                        current_page: pipelines?.data?.meta?.current_page ?? 1,
                        from: pipelines?.data?.meta?.from ?? 1,
                        last_page: pipelines?.data?.meta?.last_page ?? 1,
                        per_page: pipelines?.data?.meta?.per_page ?? 10,
                        to: pipelines?.data?.meta?.to ?? 10,
                        total: pipelines?.data?.meta?.total ?? 0,
                    }
                }}
                onPageChange={handlePageChange}
            />
        </AppLayout>
    );
}