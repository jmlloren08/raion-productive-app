import React from 'react';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from "lucide-react";

const getValue = (obj: any, path: string) => {
    return path.split('.').reduce((acc, part) => acc && acc[part], obj);
};

interface Column {
    key: string;
    label: string;
    render?: (value: any) => React.ReactNode;
    truncate?: boolean; 
}

interface PaginationMeta {
    current_page: number;
    from: number;
    last_page: number;
    per_page: number;
    to: number;
    total: number;
}

interface PaginatedData<T> {
    data: T[];
    meta: PaginationMeta;
}

interface DataTableProps<T> {
    columns: Column[];
    data: PaginatedData<T>;
    onPageChange?: (page: number) => void;
}

export function DataTable<T>({ columns, data, onPageChange }: DataTableProps<T>) {
    const items = Array.isArray(data?.data) ? data.data : [];

    return (
        <div className='p-4'>
            <Table>
                <TableHeader>
                    <TableRow>
                        {columns.map((column) => (
                            <TableHead key={column.key}>{column.label}</TableHead>
                        ))}
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {items.length === 0 ? (
                        <TableRow>
                            <TableCell colSpan={columns.length} className="text-center py-4">
                                No data available
                            </TableCell>
                        </TableRow>
                    ) : (
                        items.map((row: any, i) => (
                            <TableRow key={i}>
                                {columns.map((column) => (
                                    <TableCell 
                                        key={`${i}-${column.key}`}
                                        className={column.truncate ? 'max-w-[200px]' : undefined}
                                    >
                                        <div 
                                            className={column.truncate ? 'truncate' : undefined}
                                            title={column.truncate ? getValue(row, column.key)?.toString() || '' : undefined}
                                        >
                                            {column.render
                                                ? column.render(getValue(row, column.key))
                                                : getValue(row, column.key)?.toString() || ''}
                                        </div>
                                    </TableCell>
                                ))}
                            </TableRow>
                        ))
                    )}
                </TableBody>
            </Table>

            {/* Pagination Controls */}
            <div className="flex items-center justify-between px-4 py-4 border-t">
                <div className="flex-1 text-sm text-muted-foreground">
                    Showing {data.meta.from} to {data.meta.to} of {data.meta.total} entries
                </div>
                <div className="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="icon"
                        onClick={() => onPageChange?.(1)}
                        disabled={data.meta.current_page <= 1}
                    >
                        <ChevronsLeft className="h-4 w-4" />
                    </Button>
                    <Button
                        variant="outline"
                        size="icon"
                        onClick={() => onPageChange?.(data.meta.current_page - 1)}
                        disabled={data.meta.current_page <= 1}
                    >
                        <ChevronLeft className="h-4 w-4" />
                    </Button>
                    <div className="text-sm font-medium">
                        Page {data.meta.current_page} of {data.meta.last_page}
                    </div>
                    <Button
                        variant="outline"
                        size="icon"
                        onClick={() => onPageChange?.(data.meta.current_page + 1)}
                        disabled={data.meta.current_page >= data.meta.last_page}
                    >
                        <ChevronRight className="h-4 w-4" />
                    </Button>
                    <Button
                        variant="outline"
                        size="icon"
                        onClick={() => onPageChange?.(data.meta.last_page)}
                        disabled={data.meta.current_page >= data.meta.last_page}
                    >
                        <ChevronsRight className="h-4 w-4" />
                    </Button>
                </div>
            </div>
        </div>
    );
}
