import { DataTable } from "@/components/ui/data-table";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, router } from "@inertiajs/react";

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'type', label: 'Type' },
    { key: 'name', label: 'Name', truncate: true },
    { key: 'customField.name', label: 'Custom Field' },
    {
        key: 'archived_at',
        label: 'Archived At',
        render: (value: any) => value ? new Date(value).toLocaleString() : 'Not Available',
    }
];

interface CustomFieldOption {
    id: number;
    type: string;
    name: string;
    customField: {
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
    data: CustomFieldOption[];
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

interface CustomFieldOptionsProps {
    customFieldOptions: {
        data: LaravelPagination;
        meta: PaginationMeta;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Custom Field Options',
        href: '/custom-field-options',
    },
];

export default function CustomFieldOptions({ customFieldOptions }: CustomFieldOptionsProps) {

    const handlePageChange = (page: number) => {
        router.get('/custom-field-options', { page }, {
            preserveState: true,
            preserveScroll: true,
            only: ['customFieldOptions'],
        });
    };

    return (
        <AppLayout>
            <Head title="Custom Field Options" />
            <DataTable
                columns={columns}
                data={{
                    data: customFieldOptions?.data?.data ?? [],
                    meta: {
                        current_page: customFieldOptions?.data?.current_page ?? 1,
                        from: customFieldOptions?.data?.from ?? 1,
                        last_page: customFieldOptions?.data?.last_page ?? 1,
                        per_page: customFieldOptions?.data?.per_page ?? 10,
                        to: customFieldOptions?.data?.to ?? 0,
                        total: customFieldOptions?.data?.total ?? 0,
                    }
                }}
                onPageChange={handlePageChange}
            />
        </AppLayout>
    );
}