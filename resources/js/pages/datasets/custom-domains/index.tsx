import { DataTable } from "@/components/ui/data-table";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, router } from "@inertiajs/react";

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'type', label: 'Type' },
    { key: 'name', label: 'Name', truncate: true },
    {
        key: 'verified_at',
        label: 'Verified At',
        render: (value: any) => value ? new Date(value).toLocaleString() : 'Not Verified',

    },
    { key: 'subsidiary.name', label: 'Subsidiary' },
];

interface CustomDomain {
    id: number;
    type: string;
    name: string;
    verified_at: string | null;
    subsidiary: {
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
    data: CustomDomain[];
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

interface CustomDomainsProps {
    customDomains: {
        data: LaravelPagination;
        meta: PaginationMeta;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Custom Domains',
        href: '/custom-domains',
    },
];

export default function CustomDomains({ customDomains }: CustomDomainsProps) {
    
    const handlePageChange = (page: number) => {
        router.get('/custom-domains', { page }, { 
            preserveState: true,
            preserveScroll: true,
            only: ['customDomains'],
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Custom Domains" />
            <DataTable
                columns={columns}
                data={{
                    data: customDomains?.data?.data ?? [],
                    meta: {
                        current_page: customDomains?.data?.current_page ?? 1,
                        from: customDomains?.data?.from ?? 0,
                        last_page: customDomains?.data?.last_page ?? 1,
                        per_page: customDomains?.data?.per_page ?? 10,
                        to: customDomains?.data?.to ?? 0,
                        total: customDomains?.data?.total ?? 0,
                    },
                }}
                onPageChange={handlePageChange}
            />
        </AppLayout>
    );
}