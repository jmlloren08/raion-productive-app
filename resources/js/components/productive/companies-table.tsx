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

  return (
    <div className="rounded-md border">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>ID</TableHead>
            <TableHead>Code</TableHead>
            <TableHead>Name</TableHead>
            <TableHead>Projects Count</TableHead>
            <TableHead>Created At</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {companies.map((company) => (
            <TableRow key={company.id}>
              <TableCell>{company.id}</TableCell>
              <TableCell>{company.company_code}</TableCell>
              <TableCell className="font-medium">{company.name}</TableCell>
              <TableCell>{company.projects.length}</TableCell>
              <TableCell>{company.created_at_api && new Date(company.created_at_api).toLocaleString()}</TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </div>
  );
}
