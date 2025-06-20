import { DataTable } from "@/components/ui/data-table";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, router } from "@inertiajs/react";

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'type', label: 'Type' },
    { key: 'name', label: 'Name', truncate: true },
    { key: 'color_id', label: 'Color'},
    { key: 'icon_id', label: 'Icon' },
    {key: 'members_included', label: 'Members Included'},
];

interface Team {
    id: number;
    type: string;
    name: string;
    color_id: number;
    icon_id: number;
    members_included: number;
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
    data: Team[];
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

interface TeamsProps {
    teams: {
        data: LaravelPagination;
        meta: PaginationMeta;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Teams',
        href: '/teams',
    }
];

export default function Teams({ teams }: TeamsProps) {

    const handlePageChange = (page: number) => {
        router.get('/teams', { page }, {
            preserveState: true,
            preserveScroll: true,
            only: ['teams'],
        });
    };

    return (
        <AppLayout>
            <Head title="Teams" />
            <DataTable
                columns={columns}
                data={{
                    data: teams?.data?.data ?? [],
                    meta: {
                        current_page: teams?.data?.current_page ?? 1,
                        from: teams?.data?.from ?? 1,
                        last_page: teams?.data?.last_page ?? 1,
                        per_page: teams?.data?.per_page ?? 10,
                        to: teams?.data?.to ?? 1,
                        total: teams?.data?.total ?? 0,
                    }
                }}
            />
        </AppLayout>
    );
}