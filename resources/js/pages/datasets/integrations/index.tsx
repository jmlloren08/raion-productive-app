import { DataTable } from "@/components/ui/data-table";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, router } from "@inertiajs/react";

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'type', label: 'Type' },
    { key: 'name', label: 'Name', truncate: true },
    { key: 'subsidiary.name', label: 'Subsidiary', truncate: true },
    { key: 'project.name', label: 'Project', truncate: true },
    { key: 'creator.first_name', label: 'Creator' },
    { key: 'deal.name', label: 'Deal', truncate: true },
];

interface Integration {
    id: number;
    type: string;
    name: string;
    subsidiary: {
        name: string;
    };
    project: {
        name: string;
    };
    creator: {
        first_name: string;
    };
    deal: {
        name: string;
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
    data: Integration[];
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

interface IntegrationsProps {
    integrations: {
        data: LaravelPagination;
        meta: PaginationMeta;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Integrations',
        href: '/integrations',
    },
];

export default function Integrations({ integrations }: IntegrationsProps) {

    const handlePageChange = (page: number) => {
        router.get('/integrations', { page }, {
            preserveState: true,
            preserveScroll: true,
            only: ['integrations'],
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Integrations" />
            <DataTable
                columns={columns}
                data={{
                    data: integrations?.data?.data ?? [],
                    meta: {
                        current_page: integrations?.data?.current_page ?? 1,
                        from: integrations?.data?.from ?? 1,
                        last_page: integrations?.data?.last_page ?? 1,
                        per_page: integrations?.data?.per_page ?? 10,
                        to: integrations?.data?.to ?? 1,
                        total: integrations?.data?.total ?? 0,
                    },
                }}
                onPageChange={handlePageChange}
            />
        </AppLayout>
    );
}