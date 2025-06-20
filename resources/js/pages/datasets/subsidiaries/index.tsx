import { DataTable } from "@/components/ui/data-table";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, router } from "@inertiajs/react";

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'type', label: 'Type' },
    { key: 'name', label: 'Name', truncate: true },
    { key: 'contactEntry.name', label: 'Contact Entry', truncate: true },
    { key: 'customDomain.name', label: 'Custom Domain', truncate: true },
    { key: 'defaultTaxRate.name', label: 'Default Tax Rate' },
    { key: 'integration.name', label: 'Integration' },
    {
        key: 'archived_at',
        label: 'Archived At',
    },
];

interface Subsidiary {
    id: number;
    type: string;
    name: string;
    contactEntry: {
        name: string;
    };
    customDomain: {
        name: string;
    };
    defaultTaxRate: {
        name: string;
    };
    integration: {
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
    data: Subsidiary[];
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

interface SubsidiariesProps {
    subsidiaries: {
        data: LaravelPagination;
        meta: PaginationMeta;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Subsidiaries',
        href: '/subsidiaries',
    }
];

export default function Subsidiaries({ subsidiaries }: SubsidiariesProps) {
    
    const handlePageChange = (page: number) => {
        router.get('/subsidiaries', { page }, {
            preserveState: true,
            preserveScroll: true,
            only: ['subsidiaries'],
        });
    };

    return (
        <AppLayout>
            <Head title="Subsidiaries" />
            <div className="p-4">
                <DataTable
                    columns={columns}
                    data={{
                        data: subsidiaries?.data?.data ?? [],
                        meta: {
                            current_page: subsidiaries?.data?.meta?.current_page ?? 1,
                            from: subsidiaries?.data?.meta?.from ?? 1,
                            last_page: subsidiaries?.data?.meta?.last_page ?? 1,
                            per_page: subsidiaries?.data?.meta?.per_page ?? 10,
                            to: subsidiaries?.data?.meta?.to ?? 0,
                            total: subsidiaries?.data?.meta?.total ?? 0,
                        }
                    }}
                />
            </div>
        </AppLayout>
    );
}