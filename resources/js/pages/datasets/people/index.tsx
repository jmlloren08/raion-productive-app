import { DataTable } from "@/components/ui/data-table";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, router } from "@inertiajs/react";

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'first_name', label: 'First Name', truncate: true },
    { key: 'last_name', label: 'Last Name', truncate: true },
    { key: 'manager.first_name', label: 'Manager', truncate: true },
    { key: 'company.name', label: 'Company', truncate: true },
    { key: 'subsidiary.name', label: 'Subsidiary', truncate: true },
    { key: 'apa.target_type', label: 'Approval Policy Assignments' },
    { key: 'team.name', label: 'Team' },
    {
        key: 'created_at',
        label: 'Created At',
        render: (value: any) => value ? new Date(value).toLocaleString() : 'Not Available',
    },
];

interface Person {
    id: number;
    first_name: string;
    last_name: string;
    manager: {
        first_name: string;
    };
    company: {
        name: string;
    };
    subsidiary: {
        name: string;
    };
    apa: {
        target_type: string;
    };
    team: {
        name: string;
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
    data: Person[];
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

interface PeopleProps {
    persons: {
        data: LaravelPagination;
        meta: PaginationMeta;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'People',
        href: '/people',
    }
];

export default function People({ persons }: PeopleProps) {

    const handlePageChange = (page: number) => {
        router.get('/people', { page }, {
            preserveState: true,
            preserveScroll: true,
            only: ['persons'],
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="People" />
            <DataTable
                columns={columns}
                data={{
                    data: persons?.data?.data ?? [],
                    meta: {
                        current_page: persons?.data?.current_page ?? 1,
                        from: persons?.data?.from ?? 0,
                        last_page: persons?.data?.last_page ?? 1,
                        per_page: persons?.data?.per_page ?? 10,
                        to: persons?.data?.to ?? 0,
                        total: persons?.data?.total ?? 0,
                    }
                }}
                onPageChange={handlePageChange}
            />
        </AppLayout>
    );
}