import { DataTable } from "@/components/ui/data-table";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, router } from "@inertiajs/react";

const columns = [
    { key: 'id', label: 'ID' },
    { key: 'type', label: 'Type' },
    { key: 'name', label: 'Name', truncate: true },
    { key: 'color', label: 'Color'},
];

interface Tag {
    id: number;
    type: string;
    name: string;
    color: string;
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
    data: Tag[];
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

interface TagsProps {
    tags: {
        data: LaravelPagination;
        meta: PaginationMeta;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tags',
        href: '/tags',
    }
];

export default function Tags({ tags }: TagsProps) {

    const handlePageChange = (page: number) => {
        router.get('/tags', { page }, {
            preserveState: true,
            preserveScroll: true,
            only: ['tags'],
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tags" />
            <DataTable
                columns={columns}
                data={{
                    data: tags?.data?.data ?? [],
                    meta: {
                        current_page: tags?.data?.current_page ?? 1,
                        from: tags?.data?.from ?? 0,
                        last_page: tags?.data?.last_page ?? 1,
                        per_page: tags?.data?.per_page ?? 10,
                        to: tags?.data?.to ?? 0,
                        total: tags?.data?.total ?? 0,
                    }
                }}
                onPageChange={handlePageChange}
            />
        </AppLayout>
    );
}