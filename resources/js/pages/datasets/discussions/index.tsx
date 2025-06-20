import { DataTable } from "@/components/ui/data-table";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, router } from "@inertiajs/react";

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'type', label: 'Type' },
    { key: 'excerpt', label: 'Excerpt' },
    { key: 'page.title', label: 'Page Title'},
    {
        key: 'resolved_at',
        label: 'Resolved At',
        render: (value: any) => value ? new Date(value).toLocaleString() : 'Not Available',
    }
];

interface Discussion {
    id: number;
    type: string;
    excerpt: string;
    page: {
        title: string;
    };
    resolved_at: string | null;
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
    data: Discussion[];
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

interface DiscussionsProps {
    discussions: {
        data: LaravelPagination;
        meta: PaginationMeta;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Discussions',
        href: '/discussions',
    },
];

export default function CustomFieldOptions({ discussions }: DiscussionsProps) {
    
    const handlePageChange = (page: number) => {
        router.get('/discussions', { page }, {
            preserveState: true,
            preserveScroll: true,
            only: ['discussions'],
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Discussions" />
            <DataTable
                columns={columns}
                data={{
                    data: discussions?.data?.data ?? [],
                    meta: {
                        current_page: discussions?.data?.current_page ?? 1,
                        from: discussions?.data?.from ?? 1,
                        last_page: discussions?.data?.last_page ?? 1,
                        per_page: discussions?.data?.per_page ?? 10,
                        to: discussions?.data?.to ?? 1,
                        total: discussions?.data?.total ?? 0,
                    },
                }}
                onPageChange={handlePageChange}
            />
        </AppLayout>
    );
}