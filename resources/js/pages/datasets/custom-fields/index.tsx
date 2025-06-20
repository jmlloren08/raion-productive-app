import { DataTable } from "@/components/ui/data-table";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, router } from "@inertiajs/react";

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'type', label: 'Type' },
    { key: 'name', label: 'Name', truncate: true },
    { key: 'project.name', label: 'Project' },
    { key: 'section.name', label: 'Section' },
    { key: 'survey.name', label: 'Survey' },
    { key: 'person.name', label: 'Person' },
    { key: 'cfo.name', label: 'Custom Field Options' },
    {
        key: 'created_at_api',
        label: 'Created At',
        render: (value: any) => value ? new Date(value).toLocaleString() : 'Not Available',
    }
];

interface CustomField {
    id: number;
    type: string;
    name: string;
    project: {
        name: string;
    };
    section: {
        name: string;
    };
    survey: {
        name: string;
    };
    person: {
        name: string;
    };
    cfo: {
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
    data: CustomField[];
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

interface CustomFieldsProps {
    customFields: {
        data: LaravelPagination;
        meta: PaginationMeta;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Custom Fields',
        href: '/custom-fields',
    },
];

export default function CustomFields({ customFields }: CustomFieldsProps) {

    const handlePageChange = (page: number) => {
        router.get('/custom-fields', { page }, {
            preserveState: true,
            preserveScroll: true,
            only: ['customFields'],
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Custom Fields" />
            <DataTable
                columns={columns}
                data={{
                    data: customFields?.data?.data ?? [],
                    meta: {
                        current_page: customFields?.data?.current_page ?? 1,
                        from: customFields?.data?.from ?? 0,
                        last_page: customFields?.data?.last_page ?? 1,
                        per_page: customFields?.data?.per_page ?? 10,
                        to: customFields?.data?.to ?? 0,
                        total: customFields?.data?.total ?? 0,
                    },
                }}
                onPageChange={handlePageChange}
            />
        </AppLayout>
    );
}