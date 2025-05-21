import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "../ui/table";
import { type Company } from "@/types/productive";

interface CompaniesTableProps {
  companies: Company[];
  searchQuery?: string;
  page?: number;
  perPage?: number;
}

export function CompaniesTable({ companies }: CompaniesTableProps) {
  const formatDate = (date: string | undefined) => {
    if (!date) return "N/A";
    return new Date(date).toLocaleDateString();
  };

  return (
    <div className="rounded-md border">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Name</TableHead>
            <TableHead>ID</TableHead>
            <TableHead>Projects Count</TableHead>
            <TableHead>Created At</TableHead>
            <TableHead>Updated At</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {companies.map((company) => (
            <TableRow key={company.id}>
              <TableCell className="font-medium">{company.name}</TableCell>
              <TableCell>{company.id}</TableCell>
              <TableCell>{company.projects.length}</TableCell>
              <TableCell>{formatDate(company.createdAt)}</TableCell>
              <TableCell>{formatDate(company.updatedAt)}</TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </div>
  );
}
