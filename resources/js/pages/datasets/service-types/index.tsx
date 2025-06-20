import { DataTable } from "@/components/ui/data-table";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, router } from "@inertiajs/react";

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'type', label: 'Type' },
    { key: 'name', label: 'Name', truncate: true },
    { key: 'assignee.first_name', label: 'Assignee' },
    {
        key: 'archived_at_api',
        label: 'Archived At',
        render: (value: string | null) => value ? new Date(value).toLocaleString() : 'N/A',
    },
];

interface ServiceType {
    id: number;
    type: string;
    name: string;
    assignee: {
        first_name: string;
    };
    archived_at_api: string | null;
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
    data: ServiceType[];
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

interface ServiceTypesProps {
    serviceTypes: {
        data: LaravelPagination;
        meta: PaginationMeta;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Service Types',
        href: '/service-types',
    }
];

export default function ServiceTypes({ serviceTypes }: ServiceTypesProps) {

    const handlePageChange = (page: number) => {
        router.get('/service-types', { page }, {
            preserveState: true,
            preserveScroll: true,
            only: ['serviceTypes'],
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Service Types" />
            <DataTable
                columns={columns}
                data={{
                    data: serviceTypes?.data?.data ?? [],
                    meta: {
                        current_page: serviceTypes?.data?.current_page ?? 1,
                        from: serviceTypes?.data?.from ?? 0,
                        last_page: serviceTypes?.data?.last_page ?? 1,
                        per_page: serviceTypes?.data?.per_page ?? 10,
                        to: serviceTypes?.data?.to ?? 0,
                        total: serviceTypes?.data?.total ?? 0,
                    }
                }}
            />
        </AppLayout>
    );
}